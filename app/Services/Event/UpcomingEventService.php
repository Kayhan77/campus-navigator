<?php

namespace App\Services\Event;

use App\Models\Event;
use Carbon\Carbon;

class UpcomingEventService
{
    public function next24Hours()
    {
        $now = Carbon::now();
        $tomorrow = $now->copy()->addDay();

        return Event::whereBetween('start_time', [$now, $tomorrow])->get();
    }

    public function next7Days()
    {
        $now = Carbon::now();
        $nextWeek = $now->copy()->addDays(7);

        return Event::whereBetween('start_time', [$now, $nextWeek])->get();
    }
}
