<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\AcademicScheduleFilter;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AcademicScheduleResource;
use App\Models\AcademicSchedule;
use App\Services\AcademicScheduleService;
use Illuminate\Http\Request;

class AcademicScheduleController extends Controller
{
    public function __construct(
        private readonly AcademicScheduleService $service
    ) {}

    public function index(Request $request, AcademicScheduleFilter $filter)
    {
        $paginator = $this->service->listPaginated($filter, $request, $this->resolvePerPage($request));

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator->getCollection()->transform(fn ($s) => new AcademicScheduleResource($s));

        return ApiResponse::paginated($paginator, 'Academic schedules retrieved successfully.');
    }

    public function show(AcademicSchedule $academicSchedule)
    {
        return ApiResponse::success(
            new AcademicScheduleResource($this->service->getById($academicSchedule)),
            'Academic schedule retrieved successfully.'
        );
    }
}
