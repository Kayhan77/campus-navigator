<?php

declare(strict_types=1);

namespace App\DTOs\Room;

use App\Http\Requests\Room\UpdateRoomRequest;

final class UpdateRoomDTO
{
    public function __construct(
        public readonly ?int    $building_id = null,
        public readonly ?string $room_number = null,
        public readonly ?int    $floor       = null,
        public readonly ?int    $capacity    = null,
        public readonly ?string $type        = null,
    ) {}

    public static function fromRequest(UpdateRoomRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            building_id: isset($validated['building_id']) ? (int) $validated['building_id'] : null,
            room_number: $validated['room_number'] ?? null,
            floor:       isset($validated['floor']) ? (int) $validated['floor'] : null,
            capacity:    isset($validated['capacity']) ? (int) $validated['capacity'] : null,
            type:        $validated['type'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'building_id' => $this->building_id,
            'room_number' => $this->room_number,
            'floor'       => $this->floor,
            'capacity'    => $this->capacity,
            'type'        => $this->type,
        ], fn(mixed $value): bool => $value !== null);
    }
}
