<?php

namespace App\Services;

use App\DTOs\Room\CreateRoomDTO;
use App\DTOs\Room\UpdateRoomDTO;
use App\Models\Room;

class RoomService
{
    // List all rooms
    public function getAll()
    {
        return Room::with('building')
        ->withCount('events')
        ->get();
    }

    // Get a single room by model binding
    public function getById(Room $room): Room
    {
        return $room->load('building');
    }

    // Create a new room
    public function create(CreateRoomDTO $data): Room
    {
        return Room::create($data->toArray());
    }

    // Update existing room
    public function update(Room $room, UpdateRoomDTO $data): Room
    {
        $room->update($data->toArray());
        return $room;
    }

    // Delete a room
    public function delete(Room $room): void
    {
        $room->delete();
    }
}
