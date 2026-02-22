<?php

namespace App\Services;

use App\Models\AcademicSchedule;
use App\DTOs\AcademicSchedule\CreateAcademicScheduleDTO;
use App\DTOs\AcademicSchedule\UpdateAcademicScheduleDTO;
use Illuminate\Support\Facades\Cache;

class AcademicScheduleService
{
    public function create(CreateAcademicScheduleDTO $data): AcademicSchedule
    {
        return AcademicSchedule::create($data->toArray());
    }

    public function update(AcademicSchedule $schedule, UpdateAcademicScheduleDTO $data): AcademicSchedule
    {
        $schedule->update($data->toArray());
        return $schedule;
    }

    public function delete(AcademicSchedule $schedule): void
    {
        $schedule->delete();
    }

    public function getAll()
    {
        // Professional: use latest() or paginate if API may return many schedules
        return Cache::remember('academic_schedules', 60, function () {
            return AcademicSchedule::with('room.building')->latest()->get();
        });
        
    }

    public function getById(AcademicSchedule $schedule): AcademicSchedule
    {
        // Clean: Route Model Binding already provides the schedule
        return $schedule->load('room.building');
    }
}
