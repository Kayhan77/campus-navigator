<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AcademicScheduleResource;
use App\Models\AcademicSchedule;
use App\Services\AcademicScheduleService;
use App\Filters\AcademicScheduleFilter;
use App\Services\Search\SearchCacheService;
use Illuminate\Http\Request;

class AcademicScheduleController extends Controller
{
    public function __construct(
        private AcademicScheduleService $service
    ) {}

    public function index(Request $request, AcademicScheduleFilter $filter, SearchCacheService $cache)
    {
        $perPage  = max(1, min((int) $request->input('per_page', config('search.default_per_page', 15)), config('search.max_per_page', 50)));
        $cacheKey = SearchCacheService::buildKey('academic_schedules', $filter, $request->input('page', 1), $perPage);

        $paginator = $cache->remember('academic_schedules', $cacheKey, function () use ($filter, $request, $perPage) {
            // 'room.building' is a safe nested default.
            // AcademicSchedule::$allowedIncludes controls any client extras.
            return AcademicSchedule::filter($filter)->withAllowed($request, ['room.building'])->paginate($perPage);
        });

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
