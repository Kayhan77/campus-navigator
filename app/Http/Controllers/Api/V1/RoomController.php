<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Room\CreateRoomDTO;
use App\DTOs\Room\UpdateRoomDTO;
use App\Filters\RoomFilter;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Room\RoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\Api\V1\RoomResource;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomService $service
    ) {}

    public function index(Request $request, RoomFilter $filter)
    {
        $paginator = $this->service->listPaginated($filter, $request, $this->resolvePerPage($request));

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator->getCollection()->transform(fn ($r) => new RoomResource($r));

        return ApiResponse::paginated($paginator, 'Rooms retrieved successfully.');
    }

    public function show(Room $room)
    {
        return ApiResponse::success(
            new RoomResource($this->service->getById($room)),
            'Room retrieved successfully.'
        );
    }

    public function store(RoomRequest $request)
    {
        $this->authorize('create', Room::class);
        $dto  = CreateRoomDTO::fromRequest($request);
        $room = $this->service->create($dto);
        return ApiResponse::success(new RoomResource($room), 'Room created successfully.', 201);
    }

    public function update(UpdateRoomRequest $request, Room $room)
    {
        $this->authorize('update', $room);
        $dto     = UpdateRoomDTO::fromRequest($request);
        $updated = $this->service->update($room, $dto);
        return ApiResponse::success(new RoomResource($updated), 'Room updated successfully.');
    }

    public function destroy(Room $room)
    {
        $this->authorize('delete', $room);
        $this->service->delete($room);
        return ApiResponse::success(null, 'Room deleted successfully.');
    }
}
