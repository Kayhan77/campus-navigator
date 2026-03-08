<?php
namespace App\Services;

use App\DTOs\RoomSearchDTO;
use App\Models\Room;
use App\Services\Cache\CacheTags;
use App\Services\Search\SearchCacheService;
use Illuminate\Database\Eloquent\Collection;

class RoomSearchService
{
    public function __construct(
        private readonly SearchCacheService $cache
    ) {}

    /**
     * Search rooms by building, room number, and/or floor.
     *
     * Results are cached under the 'rooms' Redis tag so RoomObserver
     * invalidates them automatically on any room create / update / delete.
     */
    public function search(RoomSearchDTO $dto): Collection
    {
        $key = SearchCacheService::buildSimpleKey(
            CacheTags::ROOMS,
            $dto->toCacheParameters()
        );

        return $this->cache->remember(CacheTags::ROOMS, $key, function () use ($dto) {
            $query = Room::query()->with('building');

            if ($dto->building_id !== null) {
                $query->where('building_id', $dto->building_id);
            }
            if ($dto->room_number !== null) {
                $query->where('room_number', 'like', "%{$dto->room_number}%");
            }
            if ($dto->floor !== null) {
                $query->where('floor', $dto->floor);
            }

            return $query->get();
        });
    }
}
