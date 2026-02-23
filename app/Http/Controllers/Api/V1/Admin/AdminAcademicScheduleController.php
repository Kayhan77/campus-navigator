<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\AcademicSchedule\CreateAcademicScheduleDTO;
use App\DTOs\AcademicSchedule\UpdateAcademicScheduleDTO;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicSchedule\AcademicScheduleRequest;
use App\Http\Requests\AcademicSchedule\UpdateAcademicScheduleRequest;
use App\Http\Resources\Api\V1\AcademicScheduleResource;
use App\Models\AcademicSchedule;
use App\Services\AcademicScheduleService;

class AdminAcademicScheduleController extends Controller
{
    public function __construct(
        private AcademicScheduleService $service
    ) {}

    public function index()
    {
        return ApiResponse::success(
            AcademicScheduleResource::collection($this->service->getAll()),
            'Academic schedules retrieved successfully.'
        );
    }

    public function show(AcademicSchedule $academicSchedule)
    {
        return ApiResponse::success(
            new AcademicScheduleResource($this->service->getById($academicSchedule)),
            'Academic schedule retrieved successfully.'
        );
    }

    public function store(AcademicScheduleRequest $request)
    {
        $dto      = CreateAcademicScheduleDTO::fromRequest($request);
        $schedule = $this->service->create($dto);

        return ApiResponse::success(
            new AcademicScheduleResource($schedule),
            'Academic schedule created successfully.',
            201
        );
    }

    public function update(UpdateAcademicScheduleRequest $request, AcademicSchedule $academicSchedule)
    {
        $dto     = new UpdateAcademicScheduleDTO($request->validated());
        $updated = $this->service->update($academicSchedule, $dto);

        return ApiResponse::success(
            new AcademicScheduleResource($updated),
            'Academic schedule updated successfully.'
        );
    }

    public function destroy(AcademicSchedule $academicSchedule)
    {
        $this->service->delete($academicSchedule);

        return ApiResponse::success(null, 'Academic schedule deleted successfully.');
    }
}
