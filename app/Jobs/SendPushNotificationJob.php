<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Notification\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts before the job is marked as failed.
     */
    public int $tries = 3;

    /**
     * Timeout in seconds for a single job execution.
     */
    public int $timeout = 30;

    /**
     * Exponential backoff in seconds between retries: 10s → 30s → 60s.
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /**
     * @param int    $userId  Target user's ID (model binding via SerializesModels)
     * @param string $title   Notification title
     * @param string $body    Notification body
     * @param array  $data    Optional key-value data payload for the app
     */
    public function __construct(
        private readonly int    $userId,
        private readonly string $title,
        private readonly string $body,
        private readonly array  $data = []
    ) {}

    /**
     * Execute the job.
     * FirebaseNotificationService is resolved from the container automatically.
     */
    public function handle(FirebaseNotificationService $firebaseService): void
    {
        $user = User::with('deviceTokens')->find($this->userId);

        if (! $user) {
            Log::warning('[Push] Job skipped — user not found', ['user_id' => $this->userId]);
            return;
        }

        if ($user->deviceTokens->isEmpty()) {
            Log::info('[Push] Job skipped — user has no device tokens', ['user_id' => $this->userId]);
            return;
        }

        $firebaseService->sendToUser($user, $this->title, $this->body, $this->data);

        Log::info('[Push] Notification dispatched', [
            'user_id'      => $this->userId,
            'title'        => $this->title,
            'token_count'  => $user->deviceTokens->count(),
        ]);
    }

    /**
     * Handle a job that has failed all retries.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('[Push] Job permanently failed', [
            'user_id' => $this->userId,
            'title'   => $this->title,
            'error'   => $exception->getMessage(),
        ]);
    }
}
