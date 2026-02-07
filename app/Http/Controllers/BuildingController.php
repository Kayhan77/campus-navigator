<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\DTOs\Building\BuildingData;
use App\Services\BuildingService;
use App\Http\Requests\BuildingRequest;
use App\Http\Resources\BuildingResource;

class BuildingController extends Controller
{
    protected BuildingService $service;

    public function __construct(BuildingService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return BuildingResource::collection(Building::with('rooms')->get());
    }

    public function show($id)
    {
        $building = Building::with('rooms')->findOrFail($id);
        return new BuildingResource($building);
    }

    public function store(BuildingRequest $request)
    {
        $data = new BuildingData($request->validated());
        $building = $this->service->create($data);
        return new BuildingResource($building);
    }
}
