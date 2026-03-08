<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Building\CreateBuildingDTO;
use App\DTOs\Building\UpdateBuildingDTO;
use App\Filters\BuildingFilter;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Building\BuildingRequest;
use App\Http\Requests\Building\UpdateBuildingRequest;
use App\Http\Resources\Api\V1\BuildingResource;
use App\Models\Building;
use App\Services\BuildingService;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function __construct(
        private readonly BuildingService $service
    ) {}

    public function index(Request $request, BuildingFilter $filter)
    {
        $paginator = $this->service->listPaginated($filter, $request, $this->resolvePerPage($request));

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator->getCollection()->transform(fn ($b) => new BuildingResource($b));

        return ApiResponse::paginated($paginator, 'Buildings retrieved successfully.');
    }

    public function show(Building $building)
    {
        return ApiResponse::success(
            new BuildingResource($this->service->getById($building)),
            'Building retrieved successfully.'
        );
    }

    // public function store(BuildingRequest $request)
    // {
    //     $this->authorize('create', Building::class);
    //     $dto      = CreateBuildingDTO::fromRequest($request);
    //     $building = $this->service->create($dto);
    //     return ApiResponse::success(new BuildingResource($building), 'Building created successfully.', 201);
    // }

    // public function update(UpdateBuildingRequest $request, Building $building)
    // {
    //     $this->authorize('update', $building);
    //     $dto     = new UpdateBuildingDTO($request->validated());
    //     $updated = $this->service->update($building, $dto);
    //     return ApiResponse::success(new BuildingResource($updated), 'Building updated successfully.');
    // }

    // public function destroy(Building $building)
    // {
    //     $this->authorize('delete', $building);
    //     $this->service->delete($building);
    //     return ApiResponse::success(null, 'Building deleted successfully.');
    // }
}
