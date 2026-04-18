<?php

declare(strict_types=1);

namespace App\DTOs\Building;

use App\Http\Requests\Building\BuildingRequest;
use Illuminate\Http\UploadedFile;

final class CreateBuildingDTO
{
    public function __construct(
        public string $name,
        public string $type,
        public float $latitude,
        public float $longitude,
        public ?string $description = null,
        public ?string $category = null,
        public ?string $opening_hours = null,
        public ?string $notes = null,
        public ?UploadedFile $image = null,
    ) {}

    public static function fromRequest(BuildingRequest $request): self
    {
        return new self(
            $request->name,
            $request->type,
            $request->latitude,
            $request->longitude,
            $request->description,
            $request->category,
            $request->opening_hours,
            $request->notes,
            $request->file('image'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => $this->description,
            'opening_hours' => $this->opening_hours,
            'notes' => $this->notes,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
        ];
    }
}
