<?php

namespace App\Services;

use App\DTOs\Room\CreateRoomDTO;
use App\DTOs\Room\UpdateRoomDTO;
use App\Filters\RoomFilter;
use App\Models\Room;
use App\Services\Cache\CacheTags;
use App\Services\Search\SearchCacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class RoomService
{
    public function __construct(
        private readonly SearchCacheService $cache
    ) {}

    // -------------------------------------------------------------------------
    // Listings
    // -------------------------------------------------------------------------

    /**
     * Filtered, cached, paginated list for the public API.
     * Cache is tagged with CacheTags::ROOMS; RoomObserver flushes it
     * automatically on any create / update / delete.
     */
    public function listPaginated(
        RoomFilter $filter,
        Request $request,
        int $perPage
    ): LengthAwarePaginator {
        $key = SearchCacheService::buildKey(
            CacheTags::ROOMS,
            $filter,
            (int) $request->input('page', 1),
            $perPage,
        );

        return $this->cache->remember(
            CacheTags::ROOMS,
            $key,
            fn () => Room::filter($filter)->withAllowed($request, ['building'])->paginate($perPage)
        );
    }

    /**
     * Admin-only paginated list — includes building + event counts, no filter pipeline.
     */
    public function listAdminPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Room::with('building')->withCount('events')->latest()->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Single record
    // -------------------------------------------------------------------------

    public function getById(Room $room): Room
    {
        return $room->load('building');
    }

    // -------------------------------------------------------------------------
    // Writes  (cache invalidation handled by RoomObserver)
    // -------------------------------------------------------------------------

    public function create(CreateRoomDTO $dto): Room
    {
        return Room::create($dto->toArray());
    }

    public function update(Room $room, UpdateRoomDTO $dto): Room
    {
        $room->update($dto->toArray());
        return $room->fresh();
    }

    public function delete(Room $room): void
    {
        $room->delete();
    }
}
