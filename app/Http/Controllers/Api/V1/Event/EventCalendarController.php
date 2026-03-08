<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\GetCalendarEventsRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Services\Cache\CacheTags;
use App\Services\Search\SearchCacheService;
use Illuminate\Support\Carbon;

class EventCalendarController extends Controller
{
    public function __construct(
        private readonly SearchCacheService $cache
    ) {}

    /**
     * Return events for the requested month/year shaped for a calendar widget.
     *
     * Defaults to the current month if no params are supplied.
     * Bounded to a single calendar month — never loads the full table.
     * Cached under the 'events' Redis tag; EventObserver invalidates it
     * automatically on any write.
     */
    public function index(GetCalendarEventsRequest $request)
    {
        $month = $request->month();
        $year  = $request->year();

        $key = SearchCacheService::buildSimpleKey(
            CacheTags::EVENTS,
            ['view' => 'calendar', 'month' => $month, 'year' => $year, 'from' => $request->from, 'to' => $request->to]
        );

        $events = $this->cache->remember(CacheTags::EVENTS, $key, function () use ($month, $year, $request) {
            $start = $request->from
                ? Carbon::parse($request->from)->startOfDay()
                : Carbon::create($year, $month, 1)->startOfMonth();

            $end = $request->to
                ? Carbon::parse($request->to)->endOfDay()
                : $start->copy()->endOfMonth();

            return Event::select(['id', 'title', 'location', 'start_time', 'end_time'])
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_time', [$start, $end])
                      ->orWhereBetween('end_time', [$start, $end]);
                })
                ->orderBy('start_time')
                ->get();
        });

        return ApiResponse::success(
            EventResource::collection($events),
            'Calendar events retrieved successfully.'
        );
    }
}
