<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Room\CreateRoomDTO;
use App\DTOs\Room\UpdateRoomDTO;
use App\Services\RoomService;
use App\Http\Requests\Room\RoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\Api\V1\RoomResource;
use App\Models\Room;
use App\Http\Controllers\Controller;

class RoomController extends Controller
{
    protected RoomService $service;

    public function __construct(RoomService $service)
    {
        $this->service = $service;
    }

    // List all rooms
    public function index()
    {
        return RoomResource::collection($this->service->getAll());
    }

    // Show single room
    public function show(Room $room)
    {
        return new RoomResource($this->service->getById($room));
    }

    // Create new room
    public function store(RoomRequest $request)
    {
        $this->authorize('create', Room::class);

        $dto = CreateRoomDTO::fromRequest($request);
        $room = $this->service->create($dto);

        return new RoomResource($room);
    }

    // Update room
    public function update(UpdateRoomRequest $request, Room $room)
    {
        $this->authorize('update', $room);

        $dto = new UpdateRoomDTO($request->validated());
        $room = $this->service->update($room, $dto);

        return new RoomResource($room);
    }

    // Delete room
    public function destroy(Room $room)
    {
        $this->authorize('delete', $room);

        $this->service->delete($room);

        return ['message' => 'Room deleted successfully'];
    }
}
