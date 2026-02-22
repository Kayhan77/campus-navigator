<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AcademicSchedule\CreateAcademicScheduleDTO;
use App\DTOs\AcademicSchedule\UpdateAcademicScheduleDTO;
use App\Services\AcademicScheduleService;
use App\Http\Requests\AcademicSchedule\AcademicScheduleRequest;
use App\Http\Requests\AcademicSchedule\UpdateAcademicScheduleRequest;
use App\Http\Resources\Api\V1\AcademicScheduleResource;
use App\Models\AcademicSchedule;
use App\Http\Controllers\Controller;

class AcademicScheduleController extends Controller
{
    protected AcademicScheduleService $service;

    public function __construct(AcademicScheduleService $service)
    {
        $this->service = $service;
    }

    // List all schedules
    public function index()
    {
        return AcademicScheduleResource::collection($this->service->getAll());
    }

    // Show single schedule
    public function show(AcademicSchedule $academicSchedule)
    {
        return new AcademicScheduleResource($this->service->getById($academicSchedule));
    }

    // Create new schedule
    public function store(AcademicScheduleRequest $request)
    {
        $dto = CreateAcademicScheduleDTO::fromRequest($request);

        $schedule = $this->service->create($dto);

        return new AcademicScheduleResource($schedule);
    }

    // Update schedule
    public function update(UpdateAcademicScheduleRequest $request, AcademicSchedule $academicSchedule)
    {
        $this->authorize('update', $academicSchedule);

        $dto = new UpdateAcademicScheduleDTO($request->validated());

        $schedule = $this->service->update($academicSchedule, $dto);

        return new AcademicScheduleResource($schedule);
    }

    // Delete schedule
    public function destroy(AcademicSchedule $academicSchedule)
    {
        $this->authorize('delete', $academicSchedule);

        $this->service->delete($academicSchedule);

        return ['message' => 'Schedule deleted successfully'];
    }
}
