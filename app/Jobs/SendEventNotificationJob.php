<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\User;
use App\Notifications\EventReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendEventNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public string $queue = 'notifications';

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('send-event-notifications'))->expireAfter(600),
        ];
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(): void
    {
        $windowStart = now();
        $windowEnd = now()->addDay();
        $eventsProcessed = 0;
        $usersNotified = 0;
        $usersSkipped = 0;
        $usersFailed = 0;

        Event::query()
            ->select(['id', 'title', 'start_time'])
            ->whereBetween('start_time', [$windowStart, $windowEnd])
            ->orderBy('id')
            ->chunkById(100, function ($events) use (&$eventsProcessed, &$usersNotified, &$usersSkipped, &$usersFailed): void {
                foreach ($events as $event) {
                    $eventsProcessed++;

                    User::query()
                        ->select(['id', 'email'])
                        ->whereNotNull('email')
                        ->orderBy('id')
                        ->chunkById(500, function ($users) use ($event, &$usersNotified, &$usersSkipped, &$usersFailed): void {
                            foreach ($users as $user) {
                                $idempotencyKey = $this->idempotencyKey((int) $event->id, (int) $user->id);

                                if (Cache::has($idempotencyKey)) {
                                    $usersSkipped++;

                                    continue;
                                }

                                try {
                                    $user->notify(new EventReminderNotification($event));

                                    Cache::put($idempotencyKey, true, now()->addDays(2));
                                    $usersNotified++;
                                } catch (Throwable $exception) {
                                    $usersFailed++;

                                    Log::error('[EventReminder] Failed to notify user', [
                                        'event_id' => $event->id,
                                        'user_id' => $user->id,
                                        'attempt' => $this->attempts(),
                                        'error' => $exception->getMessage(),
                                    ]);
                                }
                            }
                        });
                }
            });

        Log::info('[EventReminder] Job completed', [
            'events_processed' => $eventsProcessed,
            'users_notified' => $usersNotified,
            'users_skipped' => $usersSkipped,
            'users_failed' => $usersFailed,
            'attempt' => $this->attempts(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('[EventReminder] Job permanently failed', [
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }

    private function idempotencyKey(int $eventId, int $userId): string
    {
        return "event-reminder:{$eventId}:user:{$userId}";
    }
}
