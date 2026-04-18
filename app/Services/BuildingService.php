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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BuildingService
{
    private const IMAGE_DISK = 'public';
    private const IMAGE_PATH = 'locations';

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
        $data = $dto->toArray();

        if ($dto->image !== null) {
            try {
                $data['image'] = $this->storeImage($dto->image);
            } catch (\Exception $e) {
                Log::warning('[Campus Location] Image upload failed during create', ['error' => $e->getMessage()]);
            }
        }

        return Building::create($data);
    }

    public function update(Building $building, UpdateBuildingDTO $dto): Building
    {
        $data = $dto->toArray();

        // Handle image replacement
        if ($dto->image !== null) {
            try {
                // Delete old image if exists
                if ($building->image && Storage::disk(self::IMAGE_DISK)->exists($building->image)) {
                    Storage::disk(self::IMAGE_DISK)->delete($building->image);
                }
                $data['image'] = $this->storeImage($dto->image);
            } catch (\Exception $e) {
                Log::warning('[Campus Location] Image upload failed during update', ['error' => $e->getMessage()]);
                unset($data['image']);
            }
        }

        $building->update($data);
        return $building->fresh();
    }

    public function delete(Building $building): void
    {
        // Delete image from storage
        if ($building->image && Storage::disk(self::IMAGE_DISK)->exists($building->image)) {
            Storage::disk(self::IMAGE_DISK)->delete($building->image);
        }

        $building->delete();
    }

    // -------------------------------------------------------------------------
    // Image handling
    // -------------------------------------------------------------------------

    /**
     * Store uploaded image and return relative path.
     * Throws exception on failure (caught by caller).
     */
    private function storeImage(\Illuminate\Http\UploadedFile $image): string
    {
        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

        return Storage::disk(self::IMAGE_DISK)
            ->putFileAs(self::IMAGE_PATH, $image, $filename);
    }
}
