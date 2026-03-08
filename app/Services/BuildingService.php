<?php

namespace App\Services;

use App\DTOs\Building\CreateBuildingDTO;
use App\DTOs\Building\UpdateBuildingDTO;
use App\Filters\BuildingFilter;
use App\Models\Building;
use App\Services\Cache\CacheTags;
use App\Services\Search\SearchCacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class BuildingService
{
    public function __construct(
        private readonly SearchCacheService $cache
    ) {}

    // -------------------------------------------------------------------------
    // Listings
    // -------------------------------------------------------------------------

    /**
     * Filtered, cached, paginated list for the public API.
     * Cache is tagged with CacheTags::BUILDINGS; BuildingObserver flushes it
     * automatically on any create / update / delete.
     */
    public function listPaginated(
        BuildingFilter $filter,
        Request $request,
        int $perPage
    ): LengthAwarePaginator {
        $key = SearchCacheService::buildKey(
            CacheTags::BUILDINGS,
            $filter,
            (int) $request->input('page', 1),
            $perPage,
        );

        return $this->cache->remember(
            CacheTags::BUILDINGS,
            $key,
            fn () => Building::filter($filter)->withAllowed($request, [])->paginate($perPage)
        );
    }

    /**
     * Admin-only paginated list — includes room counts, no filter pipeline.
     */
    public function listAdminPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Building::withCount('rooms')->latest()->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Single record
    // -------------------------------------------------------------------------

    public function getById(Building $building): Building
    {
        return $building->load('rooms');
    }

    // -------------------------------------------------------------------------
    // Writes  (cache invalidation handled by BuildingObserver)
    // -------------------------------------------------------------------------

    public function create(CreateBuildingDTO $dto): Building
    {
        return Building::create($dto->toArray());
    }

    public function update(Building $building, UpdateBuildingDTO $dto): Building
    {
        $building->update($dto->toArray());
        return $building->fresh();
    }

    public function delete(Building $building): void
    {
        $building->delete();
    }
}
