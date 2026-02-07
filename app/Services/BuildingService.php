<?php

namespace App\Services;

use App\Models\Building;
use App\DTOs\Building\BuildingData;

class BuildingService
{
    public function create(BuildingData $data): Building
    {
        return Building::create([
            'name' => $data->name,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'description' => $data->description,
        ]);
    }
}
