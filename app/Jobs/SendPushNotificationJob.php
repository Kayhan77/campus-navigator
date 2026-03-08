<?php

namespace App\Jobs;

use App\DTOs\Notification\NotificationPayload;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\Notification\FirebaseNotificationService;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued push notification job.
 *
 * Features:
 *  - Dedicated `notifications` queue (isolated from business-critical jobs)
 *  - Rate-limited to 50 dispatches/second via the `push-notifications` limiter
 *  - 3 retries with exponential backoff: 10s → 30s → 60s
 *  - Permanent failure written to notification_logs for post-mortem auditing
 *  - Accepts a serialized NotificationPayload (immutable DTO, no model coupling)
 */
class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum attempts before the job is permanently failed. */
    public int $tries = 3;

    /** Per-execution timeout in seconds. */
    public int $timeout = 30;

    /** Dedicated queue — keeps notifications isolated from other work. */
    public string $queue = 'notifications';

    /**
     * @param int   $userId   Target user's primary key.
     * @param array $payload  Serialized NotificationPayload (via ->toArray()).
     */
    public function __construct(
        private readonly int   $userId,
        private readonly array $payload,
    ) {}

    // =========================================================================
    // Job middleware
    // =========================================================================

    /**
     * Apply the `push-notifications` rate limiter defined in AppServiceProvider.
     * If the limit is exceeded, the job is released back onto the queue and
     * retried after the configured decay window.
     */
    public function middleware(): array
    {
        return [new RateLimited('push-notifications')];
    }

    /**
     * Stop retrying stale jobs that are no longer useful.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(15);
    }

    // =========================================================================
    // Execution
    // =========================================================================

    public function handle(FirebaseNotificationService $firebaseService): void
    {
        $payload = NotificationPayload::fromArray($this->payload);

        if ($this->alreadyDelivered($payload)) {
            Log::info('[Push] Job skipped — already delivered (idempotent)', [
                'user_id' => $this->userId,
                'type' => $payload->type,
                'event_id' => $payload->data['event_id'] ?? null,
            ]);

            return;
        }

        $user = User::query()->select(['id'])->find($this->userId);

        if (! $user) {
            Log::warning('[Push] Job skipped — user not found', [
                'user_id' => $this->userId,
            ]);
            return;
        }

        try {
            $delivered = $firebaseService->sendToUser($user, $payload);

            Log::info('[Push] Notification processed', [
                'user_id' => $this->userId,
                'type' => $payload->type,
                'tokens_delivered' => count($delivered),
            ]);
        } catch (Throwable $exception) {
            Log::warning('[Push] Attempt failed — will retry if attempts remain', [
                'user_id' => $this->userId,
                'type' => $payload->type,
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    // =========================================================================
    // Failure handling
    // =========================================================================

    /**
     * Called after all retries are exhausted.
     * Writes a permanent-failure record to notification_logs.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('[Push] Job permanently failed', [
            'user_id' => $this->userId,
            'type'    => $this->payload['type']  ?? 'unknown',
            'title'   => $this->payload['title'] ?? 'unknown',
            'error'   => $exception->getMessage(),
        ]);

        try {
            NotificationLog::create([
                'user_id'        => $this->userId,
                'event_id'       => $this->payload['data']['event_id'] ?? null,
                'type'           => $this->payload['type']             ?? 'unknown',
                'title'          => $this->payload['title']            ?? '',
                'status'         => 'failed',
                'failure_reason' => 'max_retries_exceeded: ' . $exception->getMessage(),
                'dispatched_at'  => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('[Push] Failed to write permanent failure log', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // Retry policy
    // =========================================================================

    /**
     * Exponential backoff: 10 s → 30 s → 60 s.
     * Gives FCM and network transient errors time to recover.
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    private function alreadyDelivered(NotificationPayload $payload): bool
    {
        return NotificationLog::query()
            ->where('user_id', $this->userId)
            ->where('type', $payload->type)
            ->where('event_id', $payload->data['event_id'] ?? null)
            ->where('status', 'sent')
            ->where('dispatched_at', '>=', now()->subHours(24))
            ->exists();
    }
}
