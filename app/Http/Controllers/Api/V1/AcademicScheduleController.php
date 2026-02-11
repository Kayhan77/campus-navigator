<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AcademicScheduleData;
use App\Services\AcademicScheduleService;
use App\Http\Requests\AcademicScheduleRequest;
use App\Http\Resources\Api\V1\AcademicScheduleResource;
use App\Models\AcademicSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;


class AcademicScheduleController extends Controller
{
    protected AcademicScheduleService $service;

    public function __construct(AcademicScheduleService $service)
    {
        $this->service = $service;
    }

    // List all schedules
    public function index(): JsonResponse
    {
        $schedules = $this->service->getAll();
        return response()->json(AcademicScheduleResource::collection($schedules));
    }

    // Show single schedule
    public function show(AcademicSchedule $academicSchedule): JsonResponse
    {
        $schedule = $this->service->getById($academicSchedule->id);
        return response()->json(new AcademicScheduleResource($schedule));
    }

    // Create new schedule
    public function store(AcademicScheduleRequest $request): JsonResponse
    {
        $data = AcademicScheduleData::fromRequest($request);
        $schedule = $this->service->create($data);

        return response()->json(new AcademicScheduleResource($schedule), 201);
    }

    // Update schedule
    public function update(AcademicScheduleRequest $request, AcademicSchedule $academicSchedule): JsonResponse
    {
        $data = AcademicScheduleData::fromRequest($request);
        $schedule = $this->service->update($academicSchedule, $data);

        return response()->json(new AcademicScheduleResource($schedule));
    }

    // Delete schedule
    public function destroy(AcademicSchedule $academicSchedule): JsonResponse
    {
        $this->service->delete($academicSchedule);
        return response()->json(['message' => 'Schedule deleted successfully'], 200);
    }
}
