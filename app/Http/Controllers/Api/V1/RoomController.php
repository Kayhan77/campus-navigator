<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\RoomFilter;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
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
}
