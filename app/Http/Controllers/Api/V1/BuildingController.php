<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\BuildingData;
use App\Services\BuildingService;
use App\Http\Requests\BuildingRequest;
use App\Http\Resources\Api\V1\BuildingResource;
use App\Models\Building;
use Illuminate\Routing\Controller;


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
        $data = new BuildingData($request->validated());
        $building = $this->service->create($data);

        return new BuildingResource($building);
    }

    // Update existing building
    public function update(BuildingRequest $request, Building $building)
    {
        $data = new BuildingData($request->validated());
        $building = $this->service->update($building, $data);

        return new BuildingResource($building);
    }

    // Delete building
    public function destroy(Building $building)
    {
        $this->service->delete($building);

        return response()->json([
            'message' => 'Building deleted successfully'
        ]);
    }
}
