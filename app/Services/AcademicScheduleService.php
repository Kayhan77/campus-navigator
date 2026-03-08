<?php

namespace App\Services;

use App\DTOs\AcademicSchedule\CreateAcademicScheduleDTO;
use App\DTOs\AcademicSchedule\UpdateAcademicScheduleDTO;
use App\Filters\AcademicScheduleFilter;
use App\Models\AcademicSchedule;
use App\Services\Cache\CacheTags;
use App\Services\Search\SearchCacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class AcademicScheduleService
{
    public function __construct(
        private readonly SearchCacheService $cache
    ) {}

    // -------------------------------------------------------------------------
    // Listings
    // -------------------------------------------------------------------------

    /**
     * Filtered, cached, paginated list for the public API.
     * Cache is tagged with CacheTags::ACADEMIC_SCHEDULES; the observer flushes it
     * automatically on any create / update / delete.
     */
    public function listPaginated(
        AcademicScheduleFilter $filter,
        Request $request,
        int $perPage
    ): LengthAwarePaginator {
        $key = SearchCacheService::buildKey(
            CacheTags::ACADEMIC_SCHEDULES,
            $filter,
            (int) $request->input('page', 1),
            $perPage,
        );

        return $this->cache->remember(
            CacheTags::ACADEMIC_SCHEDULES,
            $key,
            fn () => AcademicSchedule::filter($filter)
                ->withAllowed($request, ['room.building'])
                ->paginate($perPage)
        );
    }

    /**
     * Admin-only paginated list — no filter pipeline needed.
     */
    public function listAdminPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return AcademicSchedule::with('room.building')->latest()->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Single record
    // -------------------------------------------------------------------------

    public function getById(AcademicSchedule $schedule): AcademicSchedule
    {
        return $schedule->load('room.building');
    }

    // -------------------------------------------------------------------------
    // Writes  (cache invalidation handled by AcademicScheduleObserver)
    // -------------------------------------------------------------------------

    public function create(CreateAcademicScheduleDTO $dto): AcademicSchedule
    {
        return AcademicSchedule::create($dto->toArray());
    }

    public function update(AcademicSchedule $schedule, UpdateAcademicScheduleDTO $dto): AcademicSchedule
    {
        $schedule->update($dto->toArray());
        return $schedule->fresh();
    }

    public function delete(AcademicSchedule $schedule): void
    {
        $schedule->delete();
    }
}
