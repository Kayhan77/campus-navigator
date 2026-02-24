<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Models\AcademicSchedule;
use App\Models\Building;
use App\Models\Event;
use App\Models\LostItem;
use App\Models\Room;
use App\Observers\AcademicScheduleObserver;
use App\Observers\BuildingObserver;
use App\Observers\EventObserver;
use App\Observers\LostItemObserver;
use App\Observers\RoomObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPasswordResetUrl();
        $this->registerJobRateLimiters();
        $this->registerSearchObservers();
    }

    // -------------------------------------------------------------------------

    private function registerPasswordResetUrl(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return env('FRONTEND_URL') .
                "?token={$token}&email={$user->email}";
        });
    }

    /**
     * Rate limiters for queued jobs.
     *
     * `push-notifications` limits FCM dispatch throughput globally:
     *   - 50 jobs/second  (FCM best-practice ceiling)
     *   - 2 000 jobs/minute (burst guard)
     *
     * When a job hits the limit it is released back to the queue
     * after the limiter's decay window — no job is dropped.
     */
    private function registerJobRateLimiters(): void
    {
        RateLimiter::for('push-notifications', function (object $job) {
            return [
                Limit::perSecond(50),
                Limit::perMinute(2000),
            ];
        });
    }

    /**
     * Bind Eloquent observers that flush the Redis search cache
     * whenever a searchable model is created, updated, or deleted.
     */
    private function registerSearchObservers(): void
    {
        Event::observe(EventObserver::class);
        Building::observe(BuildingObserver::class);
        Room::observe(RoomObserver::class);
        LostItem::observe(LostItemObserver::class);
        AcademicSchedule::observe(AcademicScheduleObserver::class);
    }
}
