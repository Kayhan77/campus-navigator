<?php

namespace App\Services\Admin;

use App\Models\Building;
use App\Models\Event;
use App\Models\User;

class AdminDashboardService
{
    public function getStats(): array
    {
        return [
            'total_users'     => User::count(),
            'total_events'    => Event::count(),
            'total_buildings' => Building::count(),
            'latest_5_users'  => User::latest()->limit(5)->get(),
            'latest_5_events' => Event::latest()->limit(5)->get(),
        ];
    }
}
