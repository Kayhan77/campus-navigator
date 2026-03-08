<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\Cache\CacheTags;

final class AcademicScheduleObserver extends BaseModelObserver
{
    protected function tag(): string
    {
        return CacheTags::ACADEMIC_SCHEDULES;
    }
}
