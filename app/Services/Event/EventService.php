<?php

namespace App\Services\Event;

use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;
use App\Filters\EventFilter;
use App\Models\Event;
use App\Services\Cache\CacheTags;
use App\Services\Search\SearchCacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class EventService
{
    public function __construct(
        private readonly SearchCacheService $cache
    ) {}

    // -------------------------------------------------------------------------
    // Listings
    // -------------------------------------------------------------------------

    /**
     * Filtered, cached, paginated list for the public API.
     * Cache is tagged with CacheTags::EVENTS; EventObserver flushes it
     * automatically on any create / update / delete.
     */
    public function listPaginated(
        EventFilter $filter,
        Request $request,
        int $perPage
    ): LengthAwarePaginator {
        $key = SearchCacheService::buildKey(
            CacheTags::EVENTS,
            $filter,
            (int) $request->input('page', 1),
            $perPage,
        );

        return $this->cache->remember(
            CacheTags::EVENTS,
            $key,
            fn () => Event::filter($filter)->withAllowed($request, [])->paginate($perPage)
        );
    }

    /**
     * Admin-only paginated list — includes room relation, no filter pipeline.
     */
    public function listAdminPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Event::with('room')->latest()->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Single record
    // -------------------------------------------------------------------------

    public function getById(Event $event): Event
    {
        return $event;
    }

    // -------------------------------------------------------------------------
    // Writes  (cache invalidation handled by EventObserver)
    // -------------------------------------------------------------------------

    public function create(CreateEventDTO $dto, int $userId): Event
    {
        return Event::create(array_merge($dto->toArray(), ['created_by' => $userId]));
    }

    public function update(Event $event, UpdateEventDTO $dto): Event
    {
        $event->update($dto->toArray());
        return $event->fresh();
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }
}
