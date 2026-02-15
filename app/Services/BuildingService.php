<?php

namespace App\Services;

use App\Models\Building;
use App\DTOs\BUilding\CreateBuildingDTO;
use App\DTOs\BUilding\UpdateBuildingDTO;
use App\Exceptions\ApiException;

class BuildingService
{
    public function create(CreateBuildingDTO $data): Building
    {
        return Building::create([
            'name' => $data->name,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'description' => $data->description,
        ]);
    }

    public function update(Building $building, UpdateBuildingDTO $data): Building
    {
        $building->update([
            'name' => $data->name,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'description' => $data->description,
        ]);

        return $building;
    }

    public function delete(Building $building): bool
    {
        return $building->delete();
    }

    public function getAll()
    {
        return Building::with('rooms')->get();
    }

    public function getById(int $id): Building
    {
        return Building::with('rooms')->findOrFail($id);
    }
}
