<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\RoomData;
use App\Services\RoomService;
use App\Http\Requests\RoomRequest;
use App\Http\Resources\Api\V1\RoomResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;


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
        $data = new RoomData($request->validated());
        $room = $this->service->create($data);

        return response()->json(new RoomResource($room), 201);
    }

    /**
     * PUT /rooms/{id}
     */
    public function update(RoomRequest $request, int $id): JsonResponse
    {
        $data = new RoomData($request->validated());
        $room = $this->service->update($id, $data);

        return response()->json(new RoomResource($room));
    }

    /**
     * DELETE /rooms/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json([
            'message' => 'Room deleted successfully'
        ]);
    }
}
