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
        $this->floor = $data['floor'] ?? null;
    }
}
