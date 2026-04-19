<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Event;
use App\Models\Building;
use App\Models\AcademicSchedule;
use App\Models\Announcement;
use App\Models\Room;
use App\Models\News;
use App\Models\LostItem;
use App\Models\ItemClaim;
use App\Policies\EventPolicy;
use App\Policies\BuildingPolicy;
use App\Policies\RoomPolicy;
use App\Policies\AcademicSchedulePolicy;
use App\Policies\AnnouncementPolicy;
use App\Policies\NewsPolicy;
use App\Policies\LostItemPolicy;
use App\Policies\ItemClaimPolicy;

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
        Announcement::class => AnnouncementPolicy::class,
        News::class => NewsPolicy::class,
        LostItem::class => LostItemPolicy::class,
        ItemClaim::class => ItemClaimPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Optional: global gates
        Gate::define('admin-only', fn ($user) => $user->hasAnyRole(UserRole::adminRoles()));
    }
}
