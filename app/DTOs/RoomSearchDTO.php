<?php
namespace App\DTOs;

class RoomSearchDTO
{
    public ?int $building_id;
    public ?string $room_number;
    public ?int $floor;

    public function __construct(array $data)
    {
        $this->building_id = $data['building_id'] ?? null;
        $this->room_number = $data['room_number'] ?? null;
        $this->floor       = $data['floor'] ?? null;
    }

    /**
     * Deterministic array representation used by SearchCacheService::buildSimpleKey().
     * Null values are excluded so ?floor= and omitting floor produce the same key.
     */
    public function toCacheParameters(): array
    {
        return array_filter([
            'building_id' => $this->building_id,
            'room_number' => $this->room_number,
            'floor'       => $this->floor,
        ], fn ($v) => $v !== null);
    }
}
