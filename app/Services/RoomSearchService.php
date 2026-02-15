<?php
namespace App\Services;

use App\Models\Room;
use App\DTOs\RoomSearchDTO;

class RoomSearchService
{
    public function search(RoomSearchDTO $dto)
    {
        $query = Room::query()->with('building');

        if ($dto->building_id) 
            $query->where('building_id', $dto->building_id);
        if ($dto->room_number) 
            $query->where('room_number', 'like', "%{$dto->room_number}%");
        if ($dto->floor) 
            $query->where('floor', $dto->floor);

        return $query->get();
    }
}
