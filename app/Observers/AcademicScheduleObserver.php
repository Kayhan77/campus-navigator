<?php

namespace App\Observers;

class AcademicScheduleObserver extends BaseSearchObserver
{
    protected function modelTag(): string
    {
        return 'academic_schedules';
    }
}
