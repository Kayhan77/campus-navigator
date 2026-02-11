<?php

namespace App\DTOs;

class BuildingData
{
    public string $name;
    public float $latitude;
    public float $longitude;
    public ?string $description;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->latitude = $data['latitude'];
        $this->longitude = $data['longitude'];
        $this->description = $data['description'] ?? null;
    }
}
