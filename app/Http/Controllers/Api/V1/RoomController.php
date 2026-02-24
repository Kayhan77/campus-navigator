<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RoomResource;
use App\Models\Room;
use App\Services\RoomService;
use App\Filters\RoomFilter;
use App\Services\Search\SearchCacheService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(
        private RoomService $service
    ) {}

    public function index(Request $request, RoomFilter $filter, SearchCacheService $cache)
    {
        $perPage  = max(1, min((int) $request->input('per_page', config('search.default_per_page', 15)), config('search.max_per_page', 50)));
        $cacheKey = SearchCacheService::buildKey('rooms', $filter, $request->input('page', 1), $perPage);

        $paginator = $cache->remember('rooms', $cacheKey, function () use ($filter, $request, $perPage) {
            // 'building' is a safe default; client may not add extra relations
            // beyond what Room::$allowedIncludes declares.
            return Room::filter($filter)->withAllowed($request, ['building'])->paginate($perPage);
        });

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
