<?php

namespace App\Services\Notification;

use App\Models\DeviceToken;
use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Illuminate\Support\Facades\Log;


class FirebaseNotificationService
{
    public function __construct(
        private readonly Messaging $messaging
    ) {}

    /**
     * Send a push notification to a single FCM token.
     *
     * Returns true on success, false on failure (invalid/expired token is cleaned up).
     */
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = []
    ): bool {
        $message = CloudMessage::new()
            ->withToken($token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $this->messaging->send($message);
            return true;
        } catch (MessagingException $e) {
            return $this->handleSingleTokenFailure($token, $e);
        }
    }

    /**
     * Send a push notification to multiple FCM tokens using multicast
     * (single HTTP request, no looping — O(1) network cost).
     *
     * Returns an array of successfully reached tokens.
     */
    public function sendToMultipleTokens(
        array $tokens,
        string $title,
        string $body,
        array $data = []
    ): array {
        if (empty($tokens)) {
            return [];
        }

        // FCM multicast supports max 500 tokens per request
        $successTokens = [];
        $chunks = array_chunk($tokens, 500);

        foreach ($chunks as $chunk) {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            try {
                $report = $this->messaging->sendMulticast($message, $chunk);

                // Collect successfully delivered tokens
                foreach ($report->successes()->getItems() as $success) {
                    $successTokens[] = $success->target()->value();
                }

                // Delete all tokens that FCM rejected as invalid/unregistered
                foreach ($report->failures()->getItems() as $failure) {
                    $invalidToken = $failure->target()->value();
                    $this->deleteInvalidToken($invalidToken);
                    Log::info('[FCM] Removed invalid token during multicast', [
                        'token_prefix' => substr($invalidToken, 0, 10) . '...',
                        'reason'       => $failure->error()?->getMessage() ?? 'unknown',
                    ]);
                }
            } catch (MessagingException $e) {
                Log::error('[FCM] Multicast batch failed', [
                    'token_count' => count($chunk),
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        return $successTokens;
    }

    /**
     * Send a push notification to all registered devices of a given user.
     */
    public function sendToUser(
        User $user,
        string $title,
        string $body,
        array $data = []
    ): void {
        $tokens = $user->deviceTokens()
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        $this->sendToMultipleTokens($tokens, $title, $body, $data);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Handle a failure for a single-token send — removes invalid tokens.
     */
    private function handleSingleTokenFailure(string $token, MessagingException $e): bool
    {
        $errorMessage = $e->getMessage();

        // FCM error codes that indicate the token is permanently invalid
        $invalidPatterns = ['not-found', 'invalid-argument', 'unregistered'];
        $isInvalid = collect($invalidPatterns)
            ->contains(fn (string $p) => str_contains(strtolower($errorMessage), $p));

        if ($isInvalid) {
            $this->deleteInvalidToken($token);
            Log::info('[FCM] Removed invalid token after single-send failure', [
                'token_prefix' => substr($token, 0, 10) . '...',
                'reason'       => $errorMessage,
            ]);
        } else {
            // Transient error — log but keep the token
            Log::warning('[FCM] Transient send failure', [
                'token_prefix' => substr($token, 0, 10) . '...',
                'error'        => $errorMessage,
            ]);
        }

        return false;
    }

    /**
     * Safely remove a token from the database without throwing.
     */
    private function deleteInvalidToken(string $token): void
    {
        try {
            DeviceToken::where('token', $token)->delete();
        } catch (\Throwable $e) {
            Log::error('[FCM] Failed to delete invalid token from DB', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
