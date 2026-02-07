<?php

namespace App\Services;

use App\Models\AcademicSchedule;
use App\Data\AcademicScheduleData;

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
}
