<?php

namespace App\Services\Event;

use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;
use App\Filters\EventFilter;
use App\Models\Event;
use App\Models\Room;
use App\Services\Cache\CacheTags;
use App\Services\Search\SearchCacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
        $this->validateCapacity($dto->room_id, $dto->max_attendees);

        return Event::create(array_merge($dto->toArray(), ['created_by' => $userId]));
    }

    public function update(Event $event, UpdateEventDTO $dto): Event
    {
        $this->guardTerminalStatus($event);

        $roomId       = $dto->room_id       ?? $event->room_id;
        $maxAttendees = $dto->max_attendees   ?? $event->max_attendees;
        $this->validateCapacity($roomId, $maxAttendees);

        $event->update($dto->toArray());
        return $event->fresh();
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }

    // -------------------------------------------------------------------------
    // Business-rule helpers
    // -------------------------------------------------------------------------

    /**
     * Prevent max_attendees from exceeding the assigned room's capacity.
     */
    private function validateCapacity(?int $roomId, ?int $maxAttendees): void
    {
        if ($roomId === null || $maxAttendees === null) {
            return;
        }

        $room = Room::find($roomId);

        if ($room && $room->capacity > 0 && $maxAttendees > $room->capacity) {
            throw ValidationException::withMessages([
                'max_attendees' => "max_attendees ({$maxAttendees}) cannot exceed room capacity ({$room->capacity}).",
            ]);
        }
    }

    /**
     * Cancelled or completed events must not be mutated further.
     */
    private function guardTerminalStatus(Event $event): void
    {
        if (in_array($event->status, ['cancelled', 'completed'], true)) {
            throw ValidationException::withMessages([
                'status' => "A {$event->status} event cannot be updated.",
            ]);
        }
    }
}
