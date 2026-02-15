<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Room\CreateRoomDTO;
use App\DTOs\Room\UpdateRoomDTO;
use App\Services\RoomService;
use App\Http\Requests\Room\RoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\Api\V1\RoomResource;
use App\Http\Controllers\Controller;
use App\Models\Room;

class RoomController extends Controller
{
    protected RoomService $service;

    public function __construct(RoomService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /rooms
     */
    public function index()
    {
        $rooms = $this->service->index();
        return RoomResource::collection($rooms);
    }

    /**
     * GET /rooms/{id}
     */
    public function show(int $id)
    {
        $room = $this->service->show($id);
        return new RoomResource($room);
    }

    /**
     * POST /rooms
     */
    public function store(RoomRequest $request)
    {
        $this->authorize('create', Room::class);
        $data = new CreateRoomDTO($request->validated());
        $room = $this->service->create($data);

        return new RoomResource($room);
    }

    /**
     * PUT /rooms/{id}
     */
    public function update(UpdateRoomRequest $request, int $id)
    {
        $this->authorize('update', Room::class);
        $data = new UpdateRoomDTO($request->validated());
        $room = $this->service->update($id, $data);

        return new RoomResource($room);
    }

    /**
     * DELETE /rooms/{id}
     */
    public function destroy(int $id)
    {
        $this->authorize('delete', Room::class);
        $this->service->delete($id);

        return [
            'message' => 'Room deleted successfully'
        ];
    }
}
