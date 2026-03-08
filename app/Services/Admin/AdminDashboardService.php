<?php

namespace App\Services\Admin;

use App\Models\Building;
use App\Models\Event;
use App\Models\User;
use App\Services\Cache\CacheTags;
use App\Services\Search\SearchCacheService;

class AdminDashboardService
{
    public function __construct(
        private readonly SearchCacheService $cache
    ) {}

    /**
     * Aggregate stats for the admin dashboard.
     *
     * Cached under the 'dashboard' Redis tag.
     * Short TTL (60 s) keeps counts near-realtime for admins without
     * hitting the DB on every page load.
     *
     * Pass ?no_cache=1 as an admin to force a fresh read (supported by
     * SearchCacheService::shouldBypassCache()).
     */
    public function getStats(): array
    {
        $key = SearchCacheService::buildSimpleKey(CacheTags::DASHBOARD, ['view' => 'stats']);

        return $this->cache->remember(CacheTags::DASHBOARD, $key, function () {
            return [
                'total_users'     => User::count(),
                'total_events'    => Event::count(),
                'total_buildings' => Building::count(),
                'latest_5_users'  => User::latest()->limit(5)->get(),
                'latest_5_events' => Event::latest()->limit(5)->get(),
            ];
        }, 60);
    }
}
