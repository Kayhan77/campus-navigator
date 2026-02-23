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
        $stats = $this->service->getStats();

        return ApiResponse::success([
            'total_users'     => $stats['total_users'],
            'total_events'    => $stats['total_events'],
            'total_buildings' => $stats['total_buildings'],
            'latest_5_users'  => AdminUserResource::collection($stats['latest_5_users']),
            'latest_5_events' => EventResource::collection($stats['latest_5_events']),
        ], 'Dashboard data retrieved successfully.');
    }
}
