<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Room\CreateRoomDTO;
use App\DTOs\Room\UpdateRoomDTO;
use App\Services\RoomService;
use App\Http\Requests\Room\RoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\Api\V1\RoomResource;
use Illuminate\Http\JsonResponse;
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
    public function index(): JsonResponse
    {
        $rooms = $this->service->index();
        return response()->json(RoomResource::collection($rooms));
    }

    /**
     * GET /rooms/{id}
     */
    public function show(int $id): JsonResponse
    {
        $room = $this->service->show($id);
        return response()->json(new RoomResource($room));
    }

    /**
     * POST /rooms
     */
    public function store(RoomRequest $request): JsonResponse
    {
        $this->authorize('create', Room::class);
        $data = new CreateRoomDTO($request->validated());
        $room = $this->service->create($data);

        return response()->json(new RoomResource($room), 201);
    }

    /**
     * PUT /rooms/{id}
     */
    public function update(UpdateRoomRequest $request, int $id): JsonResponse
    {
        $this->authorize('update', Room::class);
        $data = new UpdateRoomDTO($request->validated());
        $room = $this->service->update($id, $data);

        return response()->json(new RoomResource($room));
    }

    /**
     * DELETE /rooms/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Room::class);
        $this->service->delete($id);

        return response()->json([
            'message' => 'Room deleted successfully'
        ]);
    }
}
