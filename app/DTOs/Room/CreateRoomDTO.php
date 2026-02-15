<?php

namespace App\DTOs\Room;

class CreateRoomDTO
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

    public function toArray(): array
    {
        return [
            'building_id' => $this->building_id,
            'room_number' => $this->room_number,
            'floor' => $this->floor,
        ];
    }
}
