<?php

namespace App\Services;

use App\Models\Building;
use App\DTOs\Building\CreateBuildingDTO;
use App\DTOs\Building\UpdateBuildingDTO;

class BuildingService
{
    public function create(CreateBuildingDTO $data): Building
    {
        return Building::create($data->toArray());
    }

    public function update(Building $building, UpdateBuildingDTO $data): Building
    {
        $building->update($data->toArray());
        return $building;
    }

    public function delete(Building $building): bool
    {
        return $building->delete();
    }

    public function getAll()
    {
        return Building::with('rooms')->get(); // optional: add latest() or paginate
    }

    public function getById(Building $building): Building
    {
        return $building->load('rooms');
    }
}
