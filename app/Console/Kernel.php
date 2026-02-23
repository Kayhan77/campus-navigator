<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Legacy notification job (kept for backwards compatibility)
        $schedule->job(new \App\Jobs\SendEventNotificationJob)->hourly();

        // FCM push reminders: runs every 15 minutes to catch upcoming events
        // Production cron: * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
        $schedule->command('app:send-event-reminders')
            ->everyFifteenMinutes()
            ->withoutOverlapping()     // prevents concurrent runs
            ->runInBackground()        // non-blocking
            ->appendOutputTo(storage_path('logs/event-reminders.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
