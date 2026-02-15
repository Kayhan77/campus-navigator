<?php
namespace App\DTOs\Building;

class UpdateBuildingDTO
{
    public ?string $name;
    public ?float $latitude;
    public ?float $longitude;
    public ?string $description;

    public function __construct(array $data)
    {
        $this->name = $data['name'] ?? null;
        $this->latitude = $data['latitude'] ?? null;
        $this->longitude = $data['longitude'] ?? null;
        $this->description = $data['description'] ?? null;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => $this->description,
        ], fn($value) => $value !== null);
    }
}
