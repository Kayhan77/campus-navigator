<?php

namespace App\Services\Notification;

use App\DTOs\Notification\NotificationPayload;
use App\Models\DeviceToken;
use App\Models\NotificationLog;
use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Production-grade Firebase Cloud Messaging service.
 *
 * Responsibilities:
 *  - Build FCM messages from a NotificationPayload DTO
 *  - Send to single tokens or multicast batches (≤500/request)
 *  - Automatically purge invalid/unregistered device tokens
 *  - Write delivery outcomes to notification_logs (analytics + auditing)
 *  - Never log full tokens; use masked prefix for traceability
 */
class FirebaseNotificationService
{
    /**
     * FCM error substrings that indicate a permanently invalid token.
     * Any transient error (e.g. 500, quota) does NOT match these.
     */
    private const INVALID_TOKEN_PATTERNS = [
        'not-found',
        'invalid-argument',
        'unregistered',
        'registration-token-not-registered',
    ];

    public function __construct(
        private readonly Messaging $messaging
    ) {}

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Send to a single FCM token.
     *
     * @return bool  true = delivered, false = failed (token cleaned up if invalid)
     */
    public function sendToToken(string $token, NotificationPayload $payload): bool
    {
        $message = $this->buildMessage($token, $payload);

        try {
            $this->messaging->send($message);

            $this->writeLog(
                userId:       $this->resolveUserId($token),
                payload:      $payload,
                status:       'sent',
                tokenCount:   1,
                successCount: 1,
            );

            return true;

        } catch (MessagingException $e) {
            return $this->handleSingleTokenFailure($token, $payload, $e);
        }
    }

    /**
     * Send to multiple FCM tokens in batches of 500 (FCM multicast limit).
     * Automatically removes tokens rejected by FCM.
     *
     * @param  string[] $tokens
     * @return string[] Successfully delivered tokens
     */
    public function sendToMultipleTokens(
        array $tokens,
        NotificationPayload $payload,
        ?int $userId = null
    ): array {
        if (empty($tokens)) {
            return [];
        }

        $successTokens = [];
        $totalFailures = 0;

        foreach (array_chunk($tokens, 500) as $chunk) {
            $message = $this->buildMessage(null, $payload);

            try {
                $report = $this->messaging->sendMulticast($message, $chunk);

                foreach ($report->successes()->getItems() as $success) {
                    $successTokens[] = $success->target()->value();
                }

                foreach ($report->failures()->getItems() as $failure) {
                    $invalidToken = $failure->target()->value();
                    $reason       = $failure->error()?->getMessage() ?? 'unknown';

                    $this->deleteInvalidToken($invalidToken);
                    $totalFailures++;

                    Log::info('[FCM] Purged invalid token (multicast)', [
                        'token_mask' => $this->maskToken($invalidToken),
                        'reason'     => $reason,
                    ]);
                }

            } catch (MessagingException $e) {
                $totalFailures += count($chunk);

                Log::error('[FCM] Multicast batch failed', [
                    'token_count' => count($chunk),
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $this->writeLog(
            userId:       $userId,
            payload:      $payload,
            status:       $totalFailures < count($tokens) ? 'sent' : 'failed',
            tokenCount:   count($tokens),
            successCount: count($successTokens),
            failureCount: $totalFailures,
        );

        return $successTokens;
    }

    /**
     * Send to all registered devices of a user.
     * Loads tokens lazily — does nothing if the user has no tokens.
     *
     * @return string[] Successfully delivered tokens
     */
    public function sendToUser(User $user, NotificationPayload $payload): array
    {
        $tokens = $user->deviceTokens()->pluck('token')->all();

        if (empty($tokens)) {
            Log::debug('[FCM] Skipped — user has no device tokens', [
                'user_id' => $user->id,
            ]);

            $this->writeLog(
                userId:    $user->id,
                payload:   $payload,
                status:    'skipped',
                tokenCount: 0,
            );

            return [];
        }

        return $this->sendToMultipleTokens($tokens, $payload, $user->id);
    }

    // =========================================================================
    // Message builder
    // =========================================================================

    /**
     * Build a CloudMessage from a NotificationPayload.
     *
     * If $token is null the message is built without a target
     * (suitable for multicast where the SDK injects tokens itself).
     */
    private function buildMessage(?string $token, NotificationPayload $payload): CloudMessage
    {
        $message = CloudMessage::new()
            ->withNotification(Notification::create($payload->title, $payload->body))
            ->withData($payload->toDataArray());

        if ($token !== null) {
            $message = $message->withToken($token);
        }

        return $message;
    }

    // =========================================================================
    // Failure handling
    // =========================================================================

    /**
     * Handle a single-token failure:
     *   - Permanently invalid  → delete token, return false
     *   - Transient error      → keep token, return false (job will retry)
     */
    private function handleSingleTokenFailure(
        string $token,
        NotificationPayload $payload,
        MessagingException $e
    ): bool {
        $errorMessage = strtolower($e->getMessage());
        $isInvalid    = collect(self::INVALID_TOKEN_PATTERNS)
            ->contains(fn (string $p) => str_contains($errorMessage, $p));

        if ($isInvalid) {
            $userId = $this->resolveUserId($token);
            $this->deleteInvalidToken($token);

            Log::info('[FCM] Purged permanently invalid token', [
                'token_mask' => $this->maskToken($token),
                'reason'     => $e->getMessage(),
            ]);

            $this->writeLog(
                userId:       $userId,
                payload:      $payload,
                status:       'failed',
                tokenCount:   1,
                failureCount: 1,
                reason:       'invalid_token: ' . $e->getMessage(),
            );
        } else {
            Log::warning('[FCM] Transient send failure — token retained', [
                'token_mask' => $this->maskToken($token),
                'error'      => $e->getMessage(),
            ]);

            $this->writeLog(
                userId:       $this->resolveUserId($token),
                payload:      $payload,
                status:       'failed',
                tokenCount:   1,
                failureCount: 1,
                reason:       'transient: ' . $e->getMessage(),
            );
        }

        return false;
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    /**
     * Safely delete a token from the database.
     * Swallows exceptions so a DB error never bubbles up into FCM logic.
     */
    private function deleteInvalidToken(string $token): void
    {
        try {
            DeviceToken::where('token', $token)->delete();
        } catch (\Throwable $e) {
            Log::error('[FCM] Failed to delete invalid token', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve the user_id that owns a given token (for log records).
     * Returns null if the token is already gone.
     */
    private function resolveUserId(string $token): ?int
    {
        return DeviceToken::where('token', $token)->value('user_id');
    }

    /**
     * Mask a token for safe logging.
     * Only the first 8 and last 4 characters are retained.
     *
     * Example: "abc12345...xyz9"
     */
    private function maskToken(string $token): string
    {
        $len = strlen($token);

        if ($len <= 12) {
            return str_repeat('*', $len);
        }

        return substr($token, 0, 8) . '...' . substr($token, -4);
    }

    /**
     * Persist a delivery outcome to notification_logs.
     * Failures here are swallowed — analytics must not break delivery.
     */
    private function writeLog(
        ?int               $userId,
        NotificationPayload $payload,
        string             $status,
        int                $tokenCount   = 0,
        int                $successCount = 0,
        int                $failureCount = 0,
        ?string            $reason       = null,
    ): void {
        if ($userId === null) {
            return;
        }

        try {
            NotificationLog::create([
                'user_id'        => $userId,
                'event_id'       => $payload->data['event_id'] ?? null,
                'type'           => $payload->type,
                'title'          => $payload->title,
                'status'         => $status,
                'token_count'    => $tokenCount,
                'success_count'  => $successCount,
                'failure_count'  => $failureCount,
                'failure_reason' => $reason,
                'dispatched_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[FCM] Failed to write notification log', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
