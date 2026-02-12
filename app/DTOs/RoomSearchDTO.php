<?php

namespace App\DTOs\Room;

class RoomSearchDTO
{
    public ?int $building_id;
    public ?int $capacity;
    public ?string $room_number;

    public function __construct(array $data)
    {
        $this->building_id = $data['building_id'] ?? null;
        $this->capacity    = $data['capacity'] ?? null;
        $this->room_number = $data['room_number'] ?? null;
    }
}
