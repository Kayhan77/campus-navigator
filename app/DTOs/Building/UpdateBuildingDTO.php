<?php

declare(strict_types=1);

namespace App\DTOs\Building;

use App\Http\Requests\Building\UpdateBuildingRequest;

final class UpdateBuildingDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?float  $latitude,
        public readonly ?float  $longitude,
        public readonly ?string $description,
    ) {}

    public static function fromRequest(UpdateBuildingRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name:        $validated['name'] ?? null,
            latitude:    isset($validated['latitude'])  ? (float) $validated['latitude']  : null,
            longitude:   isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            description: $validated['description'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name'        => $this->name,
            'latitude'    => $this->latitude,
            'longitude'   => $this->longitude,
            'description' => $this->description,
        ], fn(mixed $value): bool => $value !== null);
    }
}
