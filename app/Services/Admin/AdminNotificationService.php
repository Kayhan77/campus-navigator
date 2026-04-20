<?php

namespace App\Services\Admin;

use App\Models\DeviceToken;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class AdminNotificationService
{
    public function __construct(
        private FirebaseService $firebase
    ) {}

    /**
     * Send admin notification to users via Firebase.
     *
     * @param string $title The notification title
     * @param string $body The notification body
     * @param array|null $userIds If null or empty, send to all users. Otherwise send only to specified user IDs.
     * @return array Result with 'sent' and 'failed' counts
     */
    public function sendAdminNotification(
        string $title,
        string $body,
        ?array $userIds = null
    ): array {
        // Fetch device tokens
        $query = DeviceToken::query();

        if (!empty($userIds)) {
            $query->whereIn('user_id', $userIds);
        }

        $tokens = $query
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->get(['token', 'user_id']);

        if ($tokens->isEmpty()) {
            return [
                'sent' => 0,
                'failed' => 0,
            ];
        }

        $sent = 0;
        $failed = 0;

        // Prepare notification data
        $data = [
            'type' => 'admin_notification',
        ];

        // Send notifications
        foreach ($tokens as $deviceToken) {
            try {
                $this->firebase->sendNotification(
                    $deviceToken->token,
                    $title,
                    $body,
                    $data
                );

                $sent++;
            } catch (\Throwable $e) {
                $failed++;

                // Log error
                Log::error('Admin notification failed', [
                    'token' => $deviceToken->token,
                    'user_id' => $deviceToken->user_id,
                    'error' => $e->getMessage(),
                ]);

                // Check if token is invalid and should be deleted
                $this->handleInvalidToken($deviceToken->token, $e->getMessage());
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    /**
     * Handle invalid Firebase tokens by removing them from database.
     *
     * @param string $token The device token
     * @param string $errorMessage The error message from Firebase
     * @return void
     */
    private function handleInvalidToken(string $token, string $errorMessage): void
    {
        // List of Firebase error codes that indicate invalid/unregistered tokens
        $invalidTokenErrors = [
            'registration-token-not-registered',
            'invalid-argument',
            'mismatched-sender-id',
            'message-rate-exceeded',
        ];

        // Check if error message contains any of the invalid token indicators
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
