<?php

namespace App\Http\Controllers;

use App\DTOs\Room\RoomData;
use App\Services\RoomService;
use App\Http\Requests\RoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;

class RoomController extends Controller
{
    protected RoomService $service;

    public function __construct(RoomService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return RoomResource::collection(Room::all());
    }

    public function show($id)
    {
        $room = Room::findOrFail($id);
        return new RoomResource($room);
    }

    public function store(RoomRequest $request)
    {
        $data = new RoomData($request->validated());
        $room = $this->service->create($data);
        return new RoomResource($room);
    }
}
