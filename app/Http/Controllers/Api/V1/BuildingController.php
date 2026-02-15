<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Building\CreateBuildingDTO;
use App\DTOs\Building\UpdateBuildingDTO;
use App\Services\BuildingService;
use App\Http\Requests\Building\BuildingRequest;
use App\Http\Requests\Building\UpdateBuildingRequest;
use App\Http\Resources\Api\V1\BuildingResource;
use App\Models\Building;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;


class BuildingController extends Controller
{
    protected BuildingService $service;

    public function __construct(BuildingService $service)
    {
        $this->service = $service;
    }

    // List all buildings
    public function index()
    {
        $buildings = $this->service->getAll();
        return BuildingResource::collection($buildings);
    }

    // Show a single building
    public function show($id)
    {
        $building = $this->service->getById($id);
        return new BuildingResource($building);
    }

    // Create new building
    public function store(BuildingRequest $request)
    {
        $this->authorize('create', Building::class);
        $data = new CreateBuildingDTO($request->validated());
        $building = $this->service->create($data);

        return new BuildingResource($building);
    }

    // Update existing building
    public function update(UpdateBuildingRequest $request, Building $building)
    {
        $this->authorize('update', $building);

        $data = new UpdateBuildingDTO($request->validated());
        $building = $this->service->update($building, $data);

        return new BuildingResource($building);
    }
    // Delete building
    public function destroy(Building $building)
    {
        $this->authorize('delete', Building::class);
        $this->service->delete($building);

        return response()->json([
            'message' => 'Building deleted successfully'
        ]);
    }
}
