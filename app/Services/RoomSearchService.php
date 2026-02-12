<?php

namespace App\Services;

use App\DTOs\Room\RoomSearchDTO;
use App\Models\Room;

class RoomSearchService
{
    public function search(RoomSearchDTO $dto)
    {
        $query = Room::query();

        if ($dto->building_id) {
            $query->where('building_id', $dto->building_id);
        }

        if ($dto->room_number) {
            $query->where('room_number', 'like', "%{$dto->room_number}%");
        }

        if ($dto->capacity) {
            // assuming you have a 'capacity' column in rooms table
            $query->where('capacity', '>=', $dto->capacity);
        }

        return $query->with('building')->get();
    }
}
