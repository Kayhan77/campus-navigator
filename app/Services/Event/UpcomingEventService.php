<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Services\Cache\CacheTags;
use App\Services\Search\SearchCacheService;
use Carbon\Carbon;

class UpcomingEventService
{
    public function __construct(
        private readonly SearchCacheService $cache
    ) {}

    /**
     * Events starting within the next 24 hours.
     * Cached under the 'events' tag so EventObserver invalidates it
     * automatically on any create / update / delete.
     * Short TTL (5 min) because these windows shift with time.
     */
    public function next24Hours()
    {
        $key = SearchCacheService::buildSimpleKey(CacheTags::EVENTS, ['range' => '24h']);

        return $this->cache->remember(CacheTags::EVENTS, $key, function () {
            $now = Carbon::now();
            return Event::whereBetween('start_time', [$now, $now->copy()->addDay()])->get();
        }, 300);
    }

    /**
     * Events starting within the next 7 days.
     * Same tag as next24Hours — one observer flush clears both.
     */
    public function next7Days()
    {
        $key = SearchCacheService::buildSimpleKey(CacheTags::EVENTS, ['range' => '7d']);

        return $this->cache->remember(CacheTags::EVENTS, $key, function () {
            $now = Carbon::now();
            return Event::whereBetween('start_time', [$now, $now->copy()->addDays(7)])->get();
        }, 300);
    }
}
