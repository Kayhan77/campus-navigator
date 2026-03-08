<?php

namespace App\Services\Admin;

use App\DTOs\Building\CreateBuildingDTO;
use App\DTOs\Building\UpdateBuildingDTO;
use App\Models\Building;
use App\Services\BuildingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @deprecated Merged into BuildingService.
 *
 * This class delegates every call to BuildingService and exists only to
 * avoid breaking any code that may still reference it during transition.
 * Migrate callers to BuildingService directly and remove this file.
 */
class AdminBuildingService
{
    public function __construct(
        private readonly BuildingService $service
    ) {}

    public function listBuildings(int $perPage = 15): LengthAwarePaginator
    {
        return $this->service->listAdminPaginated($perPage);
    }

    public function create(CreateBuildingDTO $dto): Building
    {
        return $this->service->create($dto);
    }

    public function update(Building $building, UpdateBuildingDTO $dto): Building
    {
        return $this->service->update($building, $dto);
    }

    public function delete(Building $building): void
    {
        $this->service->delete($building);
    }

    public function find(int $id): Building
    {
        return Building::findOrFail($id);
    }
}
