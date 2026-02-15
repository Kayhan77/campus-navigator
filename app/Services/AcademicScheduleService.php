<?php

namespace App\Services;

use App\Models\AcademicSchedule;
use App\DTOs\AcademicSchedule\CreateAcademicScheduleDTO;
use App\DTOs\AcademicSchedule\UpdateAcademicScheduleDTO;
use App\Exceptions\ApiException;

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
        return AcademicSchedule::with('room.building')->get();
    }

    public function getById(int $id): AcademicSchedule
    {
        return AcademicSchedule::with('room.building')->findOrFail($id);
    }
}
