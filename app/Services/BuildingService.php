<?php

namespace App\Services;

use App\Models\Building;
use App\DTOs\Building\CreateBuildingDTO;
use App\DTOs\Building\UpdateBuildingDTO;
use Illuminate\Support\Facades\Cache;

class BuildingService
{
    public function create(CreateBuildingDTO $data): Building
    {
        $building = Building::create($data->toArray());
        Cache::forget('buildings'); // Invalidate cache
        return $building;
    }

    public function update(Building $building, UpdateBuildingDTO $data): Building
    {
        $building->update($data->toArray());
        Cache::forget('buildings'); // Invalidate cache
        return $building;
    }

    public function delete(Building $building): bool
    {
        return $building->delete();
        
        Cache::forget('buildings'); // Invalidate cache
    }

    public function getAll()
    {
        return Cache::remember('buildings', 60, function () {
            return Building::with('rooms')->get();
        });
    }

    public function getById(Building $building): Building
    {
        return $building->load('rooms');
    }
}
