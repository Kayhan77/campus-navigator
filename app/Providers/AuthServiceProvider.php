<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Event;
use App\Models\Building;
use App\Models\AcademicSchedule;
use App\Models\Room;
use App\Policies\EventPolicy;
use App\Policies\BuildingPolicy;
use App\Policies\RoomPolicy;
use App\Policies\AcademicSchedulePolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Event::class => EventPolicy::class,
        Building::class => BuildingPolicy::class,
        AcademicSchedule::class => AcademicSchedulePolicy::class,
        Room::class => RoomPolicy::class,
        // add other models and their policies here
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Optional: global gates
        Gate::define('admin-only', fn ($user) => $user->role === 'admin');
    }
}
