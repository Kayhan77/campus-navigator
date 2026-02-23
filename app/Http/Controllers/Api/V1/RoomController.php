<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RoomResource;
use App\Models\Room;
use App\Services\RoomService;

class RoomController extends Controller
{
    public function __construct(
        private RoomService $service
    ) {}

    public function index()
    {
        return ApiResponse::success(
            RoomResource::collection($this->service->getAll()),
            'Rooms retrieved successfully.'
        );
    }

    public function show(Room $room)
    {
        return ApiResponse::success(
            new RoomResource($this->service->getById($room)),
            'Room retrieved successfully.'
        );
    }
}
