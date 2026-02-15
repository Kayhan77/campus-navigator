<?php

namespace App\Services;

use App\DTOs\Room\CreateRoomDTO;
use App\DTOs\Room\UpdateRoomDTO;
use App\Models\Room;
use App\Exceptions\ApiException;

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
    public function create(CreateRoomDTO $data): Room
    {
        return Room::create($data->toArray());
    }

    /**
     * Update existing room
     */
    public function update(int $id, UpdateRoomDTO $data): Room
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

     public function getAll()
    {
        return Room::with('building')->get();
    }

    public function getById(int $id): Room
    {
        return Room::with('building')->findOrFail($id);
    }
}
