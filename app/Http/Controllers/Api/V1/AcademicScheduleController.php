<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AcademicScheduleResource;
use App\Models\AcademicSchedule;
use App\Services\AcademicScheduleService;

class AcademicScheduleController extends Controller
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
}
