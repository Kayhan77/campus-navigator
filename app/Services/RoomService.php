<?php

namespace App\Services;

use App\Models\Room;
use App\DTOs\Room\RoomData;

class RoomService
{
    public function create(RoomData $data): Room
    {
        return Room::create([
            'building_id' => $data->building_id,
            'room_number' => $data->room_number,
            'floor' => $data->floor,
        ]);
    }
}
