<?php

namespace App\Console\Commands;

use App\DTOs\Notification\NotificationPayload;
use App\Jobs\SendPushNotificationJob;
use App\Models\Event;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches push notification jobs for upcoming events.
 *
 * Supports multiple reminder windows per event:
 *   - 24 h before  → "starts tomorrow"
 *   - 1 h  before  → "starts in less than an hour"
 *   - 10 min before→ "starts in 10 minutes"
 *
 * Each window is tracked independently in `events.reminders_dispatched`
 * so repeated scheduler runs never send the same reminder twice.
 *
 * User-specific opt-in is respected via `users.notification_preferences`:
 *   { "enabled": true, "reminders": ["24h", "1h"], "locale": "en" }
 *
 * Run every 15 minutes by the scheduler — window tolerance set to ±7 min
 * to ensure no run falls through the cracks.
 */
class SendEventReminders extends Command
{
    protected $signature = 'app:send-event-reminders
                            {--dry-run : Preview which reminders would be sent without dispatching jobs}';

    protected $description = 'Dispatch multi-window push notification reminders for upcoming events.';

    /**
     * Reminder windows: key => minutes before event start.
     * The tolerance column defines how wide the detection window is (±).
     *
     * @var array<string, array{minutes: int, label: string, tolerance: int}>
     */
    private const REMINDER_WINDOWS = [
        '24h'   => ['minutes' => 1440, 'label' => 'tomorrow',                'tolerance' => 30],
        '1h'    => ['minutes' => 60,   'label' => 'less than an hour',        'tolerance' => 7],
        '10min' => ['minutes' => 10,   'label' => 'about 10 minutes',         'tolerance' => 7],
    ];

    public function handle(): int
    {
        $isDryRun  = (bool) $this->option('dry-run');
        $totalJobs = 0;

        foreach (self::REMINDER_WINDOWS as $windowKey => $window) {
            $count = $this->processWindow($windowKey, $window, $isDryRun);
            $totalJobs += $count;
        }

        if ($isDryRun) {
            $this->warn("[EventReminders] Dry-run mode — no jobs dispatched. {$totalJobs} would be sent.");
        } else {
            $this->info("[EventReminders] Done. {$totalJobs} job(s) dispatched across all windows.");
        }

        return self::SUCCESS;
    }

    // =========================================================================
    // Per-window processing
    // =========================================================================

    /**
     * Find events that fall inside this reminder window and haven't been
     * reminded yet for this key, then dispatch a job per qualifying user.
     *
     * @param  array{minutes: int, label: string, tolerance: int} $window
     */
    private function processWindow(string $windowKey, array $window, bool $isDryRun): int
    {
        $center    = now()->addMinutes($window['minutes']);
        $tolerance = $window['tolerance'];
        $rangeFrom = $center->copy()->subMinutes($tolerance);
        $rangeTo   = $center->copy()->addMinutes($tolerance);

        // Fetch events whose start_time falls inside the detection window
        // AND have not yet been reminded for this specific window key.
        $events = Event::query()
            ->whereBetween('start_time', [$rangeFrom, $rangeTo])
            ->where(function ($q) use ($windowKey) {
                $q->whereNull('reminders_dispatched')
                  ->orWhereJsonDoesntContain('reminders_dispatched', $windowKey);
            })
            ->get(['id', 'title', 'start_time', 'reminders_dispatched']);

        if ($events->isEmpty()) {
            $this->line("[{$windowKey}] No events in window.");
            return 0;
        }

        $this->info("[{$windowKey}] Found {$events->count()} event(s).");

        if ($isDryRun) {
            $this->table(
                ['ID', 'Title', 'Start Time'],
                $events->map(fn ($e) => [$e->id, $e->title, $e->start_time])
            );
            return $events->count();
        }

        $dispatched = 0;

        foreach ($events as $event) {
            $dispatched += $this->dispatchForEvent($event, $windowKey, $window['label']);

            // Mark this window as dispatched — prevents re-sending on the next run
            $sent   = (array) ($event->reminders_dispatched ?? []);
            $sent[] = $windowKey;
            $event->update(['reminders_dispatched' => array_unique($sent)]);

            $this->line(" ✓  [{$windowKey}] Event [{$event->id}] \"{$event->title}\" — jobs dispatched.");

            Log::info('[EventReminders] Reminder dispatched', [
                'window'     => $windowKey,
                'event_id'   => $event->id,
                'start_time' => $event->start_time,
            ]);
        }

        return $dispatched;
    }

    // =========================================================================
    // Per-event user dispatch
    // =========================================================================

    /**
     * Chunk through users who:
     *  1. Have at least one device token
     *  2. Have notifications enabled (or no preference set → default enabled)
     *  3. Have this reminder window opted-in (or no preference → default all windows)
     *
     * Dispatches one job per user so failures are isolated.
     */
    private function dispatchForEvent(Event $event, string $windowKey, string $label): int
    {
        $count = 0;

        User::query()
            ->has('deviceTokens')
            ->select(['id', 'notification_preferences'])
            ->chunk(200, function ($users) use ($event, $windowKey, $label, &$count) {
                foreach ($users as $user) {
                    if (! $this->userWantsReminder($user, $windowKey)) {
                        continue;
                    }

                    $locale  = $this->userLocale($user);
                    $payload = NotificationPayload::forEventReminder(
                        eventTitle: $event->title,
                        startsIn:   $label,
                        eventId:    $event->id,
                        startTime:  (string) $event->start_time,
                        locale:     $locale,
                    );

                    SendPushNotificationJob::dispatch($user->id, $payload->toArray())
                        ->onQueue('notifications');

                    $count++;
                }
            });

        return $count;
    }

    // =========================================================================
    // User preference helpers
    // =========================================================================

    /**
     * Returns true if the user's preferences allow this reminder window.
     * Users without a preference record receive all windows (opt-in by default).
     */
    private function userWantsReminder(User $user, string $windowKey): bool
    {
        $prefs = $user->notification_preferences;

        if (empty($prefs)) {
            return true; // no prefs → default: all windows enabled
        }

        if (isset($prefs['enabled']) && $prefs['enabled'] === false) {
            return false;
        }

        if (! empty($prefs['reminders']) && is_array($prefs['reminders'])) {
            return in_array($windowKey, $prefs['reminders'], true);
        }

        return true;
    }

    /**
     * Resolve the BCP-47 locale from user preferences, defaulting to 'en'.
     */
    private function userLocale(User $user): string
    {
        return $user->notification_preferences['locale'] ?? 'en';
    }
}
