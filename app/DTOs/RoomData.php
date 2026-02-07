<?php

namespace App\DTOs\Room;

class RoomData
{
    public int $building_id;
    public string $room_number;
    public ?int $floor;

    public function __construct(array $data)
    {
        $this->building_id = $data['building_id'];
        $this->room_number = $data['room_number'];
        $this->floor = $data['floor'] ?? null;
    }
}
