<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AcademicSchedule\CreateAcademicScheduleDTO;
use App\DTOs\AcademicSchedule\UpdateAcademicScheduleDTO;
use App\Services\AcademicScheduleService;
use App\Http\Requests\AcademicSchedule\AcademicScheduleRequest;
use App\Http\Requests\AcademicSchedule\UpdateAcademicScheduleRequest;
use App\Http\Resources\Api\V1\AcademicScheduleResource;
use App\Models\AcademicSchedule;
use Illuminate\Routing\Controller;


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
        $schedules = $this->service->getAll();
        return AcademicScheduleResource::collection($schedules);
    }

    // Show single schedule
    public function show(AcademicSchedule $academicSchedule)
    {
        $schedule = $this->service->getById($academicSchedule->id);
        return new AcademicScheduleResource($schedule);
    }

    // Create new schedule
    public function store(AcademicScheduleRequest $request)
    {
        $data = CreateAcademicScheduleDTO::fromRequest($request);
        $schedule = $this->service->create($data);

        return new AcademicScheduleResource($schedule);
    }

    // Update schedule
    public function update(UpdateAcademicScheduleRequest $request, AcademicSchedule $academicSchedule)
    {
            $this->authorize('update', $academicSchedule);

        $data = new UpdateAcademicScheduleDTO($request->validated());
        $schedule = $this->service->update($academicSchedule, $data);

        return new AcademicScheduleResource($schedule);
    }

    // Delete schedule
    public function destroy(AcademicSchedule $academicSchedule)
    {
        $this->service->delete($academicSchedule);
        return[
            'message' => 'Schedule deleted successfully'
        ];
    }
}
