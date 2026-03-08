<?php

declare(strict_types=1);

namespace App\DTOs\Room;

use App\Http\Requests\Room\UpdateRoomRequest;

final class UpdateRoomDTO
{
    public function __construct(
        public readonly ?int    $building_id,
        public readonly ?string $room_number,
        public readonly ?int    $floor,
    ) {}

    public static function fromRequest(UpdateRoomRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            building_id: isset($validated['building_id']) ? (int) $validated['building_id'] : null,
            room_number: $validated['room_number'] ?? null,
            floor:       isset($validated['floor']) ? (int) $validated['floor'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'building_id' => $this->building_id,
            'room_number' => $this->room_number,
            'floor'       => $this->floor,
        ], fn(mixed $value): bool => $value !== null);
    }
}
