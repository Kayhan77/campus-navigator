<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Building\CreateBuildingDTO;
use App\DTOs\Building\UpdateBuildingDTO;
use App\Services\BuildingService;
use App\Http\Requests\Building\BuildingRequest;
use App\Http\Requests\Building\UpdateBuildingRequest;
use App\Http\Resources\Api\V1\BuildingResource;
use App\Models\Building;
use App\Http\Controllers\Controller;
use App\Filters\BuildingFilter;
use App\Services\Search\SearchCacheService;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    protected BuildingService $service;

    public function __construct(BuildingService $service)
    {
        $this->service = $service;
    }

    // List all buildings
    public function index(Request $request, BuildingFilter $filter, SearchCacheService $cache)
    {
        $perPage  = max(1, min((int) $request->input('per_page', config('search.default_per_page', 15)), config('search.max_per_page', 50)));
        $cacheKey = SearchCacheService::buildKey('buildings', $filter, $request->input('page', 1), $perPage);

        $paginator = $cache->remember('buildings', $cacheKey, function () use ($filter, $request, $perPage) {
            return Building::filter($filter)->withAllowed($request, [])->paginate($perPage);
        });

        $paginator->getCollection()->transform(fn ($b) => new BuildingResource($b));

        return ApiResponse::paginated($paginator, 'Buildings retrieved successfully.');
    }

    // Show single building
    public function show(Building $building)
    {
        return new BuildingResource($this->service->getById($building));
    }

    // Create building
    // public function store(BuildingRequest $request)
    // {
    //     $this->authorize('create', Building::class);

    //     $dto = CreateBuildingDTO::fromRequest($request);

    //     $building = $this->service->create($dto);

    //     return new BuildingResource($building);
    // }

    // // Update building
    // public function update(UpdateBuildingRequest $request, Building $building)
    // {
    //     $this->authorize('update', $building);

    //     $dto = new UpdateBuildingDTO($request->validated());

    //     $building = $this->service->update($building, $dto);

    //     return new BuildingResource($building);
    // }

    // // Delete building
    // public function destroy(Building $building)
    // {
    //     $this->authorize('delete', $building);

    //     $this->service->delete($building);

    //     return ['message' => 'Building deleted successfully'];
    // }
}
