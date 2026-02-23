<?php

namespace App\Services\Admin;

use App\DTOs\Building\CreateBuildingDTO;
use App\DTOs\Building\UpdateBuildingDTO;
use App\Exceptions\ApiException;
use App\Models\Building;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminBuildingService
{
    public function listBuildings(int $perPage = 15): LengthAwarePaginator
    {
        return Building::withCount('rooms')->latest()->paginate($perPage);
    }

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

    public function find(int $id): Building
    {
        $building = Building::find($id);

        if (!$building) {
            throw new ApiException('Building not found.', 404);
        }

        return $building;
    }
}
