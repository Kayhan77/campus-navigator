<?php

namespace App\Services\Notification;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Central notification service that handles:
 * 1. Storing notifications in database
 * 2. Creating notification_recipients entries
 * 3. Sending Firebase notifications
 * 4. Tracking delivery and handling invalid tokens
 *
 * All application notifications (events, news, announcements) should use this service.
 */
class NotificationService
{
    public function __construct(
        private FirebaseService $firebase
    ) {}

    /**
     * Send and store a notification for users.
     *
     * This is the CENTRAL method for all notifications in the system.
     * It ensures every notification is:
     * 1. Stored in notifications table
     * 2. Linked to users via notification_recipients
     * 3. Sent via Firebase
     *
     * @param string $title Notification title
     * @param string $message Notification message/body
     * @param string $type Type of notification (event, news, announcement, admin, system)
     * @param array|null $data JSON data to store (e.g., event_id, news_id)
     * @param array|null $userIds Specific user IDs to target. If null, sends to all users.
     * @param int|null $senderId ID of the user who triggered this notification (admin)
     *
     * @return array Result with keys: 'sent', 'failed', 'notification_id'
     */
    public function sendAndStoreNotification(
        string $title,
        string $message,
        string $type = 'system',
        ?array $data = null,
        ?array $userIds = null,
        ?int $senderId = null
    ): array {
        $payload = DB::transaction(function () use ($title, $message, $type, $data, $userIds, $senderId): array {
            // Step 1: Create notification record
            $notification = $this->createNotification($title, $message, $type, $data, $senderId);

            // Step 2: Determine target users
            $targetUsers = $this->getTargetUsers($userIds);
            $targetUserIds = array_values(array_unique($targetUsers->pluck('id')->toArray()));

            if (empty($targetUserIds)) {
                return [
                    'notification' => $notification,
                    'tokens' => collect(),
                    'data' => $this->buildNotificationData($notification),
                ];
            }

            // Step 3: Create notification_recipients entries
            $this->createNotificationRecipients($notification->id, $targetUserIds);

            // Step 4: Fetch tokens for filtered users
            $tokens = $this->getDeviceTokensForUsers($targetUserIds);

            return [
                'notification' => $notification,
                'tokens' => $tokens,
                'data' => $this->buildNotificationData($notification),
            ];
        });

        /** @var Notification $notification */
        $notification = $payload['notification'];
        /** @var Collection<int, DeviceToken> $tokens */
        $tokens = $payload['tokens'];
        /** @var array<string, mixed> $firebaseData */
        $firebaseData = $payload['data'];

        // Step 5: Send Firebase notifications after DB records are safely committed
        $delivery = $this->sendFirebaseNotifications($notification, $tokens, $firebaseData);

        return array_merge($delivery, ['notification_id' => $notification->id]);
    }

    /**
     * Create the notification record in database.
     */
    private function createNotification(
        string $title,
        string $message,
        string $type,
        ?array $data,
        ?int $senderId
    ): Notification {
        return Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
            'sender_id' => $senderId,
            'target_role' => 'all', // For backward compatibility
        ]);
    }

    /**
     * Get target users based on criteria.
     * Respects notification preferences.
     */
    private function getTargetUsers(?array $userIds = null)
    {
        $query = User::query();

        // If specific users requested, use them
        if (!empty($userIds)) {
            $query->whereIn('id', $userIds);
        }

        // Apply notification preferences filter
        $this->applyNotificationPreferencesFilter($query);

        return $query->get(['id', 'name', 'email']);
    }

    /**
     * Fetch valid device tokens for the target users.
     */
    private function getDeviceTokensForUsers(array $userIds): Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        return DeviceToken::query()
            ->whereIn('user_id', $userIds)
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->get(['id', 'user_id', 'token']);
    }

    /**
     * Build Firebase data payload from notification fields.
     */
    private function buildNotificationData(Notification $notification): array
    {
        $data = [
            'type' => $notification->type,
            'notification_id' => (string) $notification->id,
        ];

        if ($notification->data && is_array($notification->data)) {
            $data = array_merge($data, $notification->data);
        }

        return $data;
    }

    /**
     * Create notification_recipients entries for target users.
     * Uses bulk insert for performance.
     */
    private function createNotificationRecipients(int $notificationId, array $userIds): void
    {
        $records = array_map(function ($userId) use ($notificationId) {
            return [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $userIds);

        // Insert in batches to avoid issues with large datasets
        foreach (array_chunk($records, 1000) as $batch) {
            NotificationRecipient::insert($batch);
        }
    }

    /**
     * Send Firebase notifications to users and track delivery.
     */
    private function sendFirebaseNotifications(
        Notification $notification,
        Collection $tokens,
        array $data
    ): array {
        $sent = 0;
        $failed = 0;
        $deliveredUserIds = [];

        if ($tokens->isEmpty()) {
            return [
                'sent' => 0,
                'failed' => 0,
            ];
        }

        foreach ($tokens as $token) {
            try {
                $this->firebase->sendNotification(
                    $token->token,
                    $notification->title,
                    $notification->message,
                    $data,
                    true
                );

                $sent++;
                $deliveredUserIds[$token->user_id] = true;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Failed to send notification to device', [
                    'device_token_id' => $token->id,
                    'user_id' => $token->user_id,
                    'notification_id' => $notification->id,
                    'notification_type' => $notification->type,
                    'error' => $e->getMessage(),
                ]);

                // Handle invalid tokens
                $this->handleInvalidToken($token->token, $e->getMessage());
            }
        }

        if (! empty($deliveredUserIds)) {
            NotificationRecipient::where('notification_id', $notification->id)
                ->whereIn('user_id', array_keys($deliveredUserIds))
                ->update(['delivered_at' => now()]);
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    /**
     * Filter query to respect user notification preferences.
     */
    private function applyNotificationPreferencesFilter(Builder $query): void
    {
        $query->where(function (Builder $preferenceQuery): void {
            $preferenceQuery
                ->whereNull('notification_preferences')
                ->orWhere('notification_preferences->enabled', true);
        });
    }

    /**
     * Handle invalid Firebase tokens by removing them from database.
     */
    private function handleInvalidToken(string $token, string $errorMessage): void
    {
        $invalidTokenErrors = [
            'registration-token-not-registered',
            'invalid-argument',
            'mismatched-sender-id',
        ];

        $shouldDelete = false;
        foreach ($invalidTokenErrors as $errorCode) {
            if (stripos($errorMessage, $errorCode) !== false) {
                $shouldDelete = true;
                break;
            }
        }

        if ($shouldDelete) {
            try {
                DeviceToken::where('token', $token)->delete();
                Log::warning('Deleted invalid device token', [
                    'token' => $token,
                    'error' => $errorMessage,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to delete invalid device token', [
                    'token' => $token,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
