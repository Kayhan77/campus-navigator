<?php

namespace App\Services;

use App\DTOs\RoomData;
use App\Models\Room;

class RoomService
{
    /**
     * Get all rooms
     */
    public function index()
    {
        return Room::with('building')->get();
    }

    /**
     * Get a single room by ID
     */
    public function show(int $id): Room
    {
        return Room::with('building')->findOrFail($id);
    }

    /**
     * Create a new room
     */
    public function create(RoomData $data): Room
    {
        return Room::create([
            'building_id' => $data->building_id,
            'room_number' => $data->room_number,
            'floor' => $data->floor,
        ]);
    }

    /**
     * Update existing room
     */
    public function update(int $id, RoomData $data): Room
    {
        $room = Room::findOrFail($id);
        $room->update((array)$data);

        return $room;
    }

    /**
     * Delete a room
     */
    public function delete(int $id): void
    {
        $room = Room::findOrFail($id);
        $room->delete();
    }
}
