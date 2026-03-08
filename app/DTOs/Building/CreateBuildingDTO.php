<?php

declare(strict_types=1);

namespace App\DTOs\Building;

use App\Http\Requests\Building\BuildingRequest;

final class CreateBuildingDTO
{
    public function __construct(
        public string $name,
        public float $latitude,
        public float $longitude,
        public ?string $description = null
    ) {}

    public static function fromRequest(BuildingRequest $request): self
    {
        return new self(
            $request->name,
            $request->latitude,
            $request->longitude,
            $request->description
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => $this->description,
        ];
    }
}
