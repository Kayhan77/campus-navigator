<?php

declare(strict_types=1);

namespace App\DTOs\Building;

use App\Http\Requests\Building\UpdateBuildingRequest;
use Illuminate\Http\UploadedFile;

final class UpdateBuildingDTO
{
    public function __construct(
        public readonly ?string        $name = null,
        public readonly ?string        $type = null,
        public readonly ?string        $category = null,
        public readonly ?float         $latitude = null,
        public readonly ?float         $longitude = null,
        public readonly ?string        $description = null,
        public readonly ?string        $opening_hours = null,
        public readonly ?string        $notes = null,
        public readonly ?UploadedFile  $image = null,
    ) {}

    public static function fromRequest(UpdateBuildingRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name:           $validated['name'] ?? null,
            type:           $validated['type'] ?? null,
            category:       $validated['category'] ?? null,
            latitude:       isset($validated['latitude'])  ? (float) $validated['latitude']  : null,
            longitude:      isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            description:    $validated['description'] ?? null,
            opening_hours:  $validated['opening_hours'] ?? null,
            notes:          $validated['notes'] ?? null,
            image:          $request->file('image'),
        );
    }

    public function toArray(): array
    {
        $data = [
            'name'           => $this->name,
            'type'           => $this->type,
            'category'       => $this->category,
            'latitude'       => $this->latitude,
            'longitude'      => $this->longitude,
            'description'    => $this->description,
            'opening_hours'  => $this->opening_hours,
            'notes'          => $this->notes,
        ];

        if ($this->image !== null) {
            $data['image'] = null;
        }

        return array_filter(
            $data,
            fn(mixed $value): bool => $value !== null || $this->image !== null
        );
    }
}
