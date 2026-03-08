<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\Building\CreateBuildingDTO;
use App\DTOs\Building\UpdateBuildingDTO;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Building\BuildingRequest;
use App\Http\Requests\Building\UpdateBuildingRequest;
use App\Http\Resources\Api\V1\BuildingResource;
use App\Models\Building;
use App\Services\BuildingService;

class AdminBuildingController extends Controller
{
    public function __construct(
        private readonly BuildingService $service
    ) {}

    public function index()
    {
        $buildings = $this->service->listAdminPaginated()
            ->through(fn ($building) => new BuildingResource($building));

        return ApiResponse::paginated($buildings, 'Buildings retrieved successfully.');
    }

    public function show(Building $building)
    {
        return ApiResponse::success(
            new BuildingResource($this->service->getById($building)),
            'Building retrieved successfully.'
        );
    }

    public function store(BuildingRequest $request)
    {
        $dto      = CreateBuildingDTO::fromRequest($request);
        $building = $this->service->create($dto);

        return ApiResponse::success(new BuildingResource($building), 'Building created successfully.', 201);
    }

    public function update(UpdateBuildingRequest $request, Building $building)
    {
        $dto     = UpdateBuildingDTO::fromRequest($request);
        $updated = $this->service->update($building, $dto);

        return ApiResponse::success(new BuildingResource($updated), 'Building updated successfully.');
    }

    public function destroy(Building $building)
    {
        $this->service->delete($building);

        return ApiResponse::success(null, 'Building deleted successfully.');
    }
}
