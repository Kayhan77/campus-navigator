<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;
use App\Services\Event\EventService;
use App\Http\Requests\Event\EventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Http\Controllers\Controller;
use App\Filters\EventFilter;
use App\Services\Search\SearchCacheService;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    protected EventService $service;

    public function __construct(EventService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request, EventFilter $filter, SearchCacheService $cache)
    {
        // max(1,...) prevents paginate(0); min(...,50) caps runaway per_page values.
        $perPage  = max(1, min((int) $request->input('per_page', config('search.default_per_page', 15)), config('search.max_per_page', 50)));
        $cacheKey = SearchCacheService::buildKey('events', $filter, $request->input('page', 1), $perPage);

        $paginator = $cache->remember('events', $cacheKey, function () use ($filter, $request, $perPage) {
            // withAllowed() only loads relations listed in Event::$allowedIncludes.
            // Raw query params (per_page, sort_by, etc.) can never reach ->with().
            return Event::filter($filter)->withAllowed($request, [])->paginate($perPage);
        });

        $paginator->getCollection()->transform(fn ($e) => new EventResource($e));

        return ApiResponse::paginated($paginator, 'Events retrieved successfully.');
    }

    public function show(Event $event)
    {
        return new EventResource($event);
    }

    // public function store(EventRequest $request)
    // {
    //     $this->authorize('create', Event::class);
    //     $data = new CreateEventDTO($request->validated());
    //     $event = $this->service->create($data, $request->user()->id);
    //     return new EventResource($event);
    // }

    // public function update(UpdateEventRequest $request, Event $event)
    // {
    //     $this->authorize('update', $event);
    //     $data = new UpdateEventDTO($request->validated());
    //     $event = $this->service->update($event, $data);
    //     return new EventResource($event);
    // }

    // public function destroy(Event $event)
    // {
    //     $this->authorize('delete', $event);
    //     $this->service->delete($event);
    //     return [
    //         'message' => 'Event deleted successfully'
    //     ];
    // }
}
