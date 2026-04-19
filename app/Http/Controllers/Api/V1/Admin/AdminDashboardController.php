<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventResource;
use App\Http\Resources\Api\V1\Admin\AdminUserResource;
use App\Services\Admin\AdminDashboardService;

class AdminDashboardController extends Controller
{
    public function __construct(
        private AdminDashboardService $service
    ) {}

    public function index()
    {
        $dashboard = $this->service->getStats();

        return ApiResponse::success([
            'stats' => [
                'total_users' => $dashboard['total_users'],
                'total_events' => $dashboard['total_events'],
                'total_buildings' => $dashboard['total_buildings'],
                'total_news' => $dashboard['total_news'],
                'total_announcements' => $dashboard['total_announcements'],
                'total_lost_items' => $dashboard['total_lost_items'],
                'total_found_items' => $dashboard['total_found_items'],
            ],
            'latest' => [
                'users' => AdminUserResource::collection($dashboard['latest_users']),
                'events' => EventResource::collection($dashboard['latest_events']),
            ],
            'activity' => [
                'users_per_day' => $dashboard['users_per_day'],
                'users_per_month' => $dashboard['users_per_month'],
                'events_per_day' => $dashboard['events_per_day'],
                'events_per_month' => $dashboard['events_per_month'],
                'lost_items_per_day' => $dashboard['lost_items_per_day'],
                'lost_items_per_month' => $dashboard['lost_items_per_month'],
            ],
        ], 'Dashboard data retrieved successfully.');
    }
}
