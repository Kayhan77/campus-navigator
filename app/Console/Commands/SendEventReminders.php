<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotificationJob;
use App\Models\Event;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:send-event-reminders
                            {--dry-run : Preview which events would be notified without dispatching jobs}';

    /**
     * The console command description.
     */
    protected $description = 'Dispatch push notification jobs for events starting within the next hour.';

    public function handle(): int
    {
        $windowStart = now();
        $windowEnd   = now()->addHour();

        $this->info("[EventReminders] Scanning events between {$windowStart} and {$windowEnd}");

        // Fetch only events that:
        // 1. Start within the next hour
        // 2. Have NOT yet had a reminder dispatched
        $events = Event::query()
            ->whereBetween('start_time', [$windowStart, $windowEnd])
            ->whereNull('reminder_sent_at')
            ->get(['id', 'title', 'start_time']);

        if ($events->isEmpty()) {
            $this->info('[EventReminders] No upcoming events require reminders.');
            return self::SUCCESS;
        }

        $this->info("[EventReminders] Found {$events->count()} event(s) to process.");

        if ($this->option('dry-run')) {
            $this->table(['ID', 'Title', 'Start Time'], $events->map(fn ($e) => [
                $e->id, $e->title, $e->start_time,
            ]));
            $this->warn('[EventReminders] Dry-run mode — no jobs dispatched.');
            return self::SUCCESS;
        }

        foreach ($events as $event) {
            $this->dispatchRemindersForEvent($event);

            // Mark reminder as sent to prevent duplicate dispatching
            $event->update(['reminder_sent_at' => now()]);

            $this->line(" ✓  Event [{$event->id}] \"{$event->title}\" — reminder dispatched.");

            Log::info('[EventReminders] Reminder jobs dispatched', [
                'event_id'    => $event->id,
                'event_title' => $event->title,
                'start_time'  => $event->start_time,
            ]);
        }

        $this->info("[EventReminders] Done. {$events->count()} event(s) processed.");

        return self::SUCCESS;
    }

    /**
     * Chunk through all users with at least one device token and
     * dispatch a push notification job for each — avoids loading
     * all users into memory at once (N+1 + memory safe).
     */
    private function dispatchRemindersForEvent(Event $event): void
    {
        User::query()
            ->has('deviceTokens')                         // only users with tokens
            ->select(['id'])                              // only load what we need
            ->chunk(200, function ($users) use ($event) {
                foreach ($users as $user) {
                    SendPushNotificationJob::dispatch(
                        $user->id,
                        '🔔 Event Reminder',
                        "\"{$event->title}\" starts in less than an hour!",
                        [
                            'event_id'   => (string) $event->id,
                            'start_time' => (string) $event->start_time,
                            'type'       => 'event_reminder',
                        ]
                    );
                }
            });
    }
}
