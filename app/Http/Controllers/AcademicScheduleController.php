<?php

namespace App\Http\Controllers;

use App\Models\AcademicSchedule;
use App\Http\Requests\AcademicScheduleRequest;
use App\Data\AcademicScheduleData;
use App\Services\AcademicScheduleService;
use App\Http\Resources\AcademicScheduleResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AcademicScheduleController extends Controller
{
    protected AcademicScheduleService $service;

    public function __construct(AcademicScheduleService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $schedules = AcademicSchedule::with('room.building')->get();
        return response()->json(AcademicScheduleResource::collection($schedules));
    }

    public function store(AcademicScheduleRequest $request): JsonResponse
    {
        $data = AcademicScheduleData::fromRequest($request);
        $schedule = $this->service->create($data);

        return response()->json(new AcademicScheduleResource($schedule), 201);
    }

    public function show(AcademicSchedule $academicSchedule): JsonResponse
    {
        return response()->json(new AcademicScheduleResource($academicSchedule->load('room.building')));
    }

    public function update(AcademicScheduleRequest $request, AcademicSchedule $academicSchedule): JsonResponse
    {
        $data = AcademicScheduleData::fromRequest($request);
        $schedule = $this->service->update($academicSchedule, $data);

        return response()->json(new AcademicScheduleResource($schedule));
    }

    public function destroy(AcademicSchedule $academicSchedule): JsonResponse
    {
        $this->service->delete($academicSchedule);
        return response()->json(null, 204);
    }
}
