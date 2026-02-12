<?php

namespace App\Services;

use App\Models\AcademicSchedule;
use App\DTOs\AcademicScheduleData;
use App\Exceptions\ApiException;

class AcademicScheduleService
{
    public function create(AcademicScheduleData $data): AcademicSchedule
    {
        return AcademicSchedule::create($data->toArray());
    }

    public function update(AcademicSchedule $schedule, AcademicScheduleData $data): AcademicSchedule
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
        return AcademicSchedule::with('room.building')->get();
    }

    public function getById(int $id): AcademicSchedule
    {
        return AcademicSchedule::with('room.building')->findOrFail($id);
    }
}
