<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomSearchRequest;
use App\Services\RoomSearchService;
use App\Http\Resources\Api\V1\RoomResource;
use App\DTOs\RoomSearchDTO;

class RoomSearchController extends Controller
{
    protected RoomSearchService $service;

    public function __construct(RoomSearchService $service)
    {
        $this->service = $service;
    }

    public function index(RoomSearchRequest $request)
    {
        $dto = new RoomSearchDTO($request->validated());

        $rooms = $this->service->search($dto);

        return RoomResource::collection($rooms);
    }
}
