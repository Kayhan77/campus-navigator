<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendAdminNotificationRequest;
use App\Services\Admin\AdminNotificationService;

class AdminNotificationController extends Controller
{
    public function __construct(
        private AdminNotificationService $service
    ) {}

    public function send(SendAdminNotificationRequest $request)
    {
        $result = $this->service->sendAdminNotification(
            title: $request->validated('title'),
            body: $request->validated('body'),
            userIds: $request->validated('user_ids')
        );

        return ApiResponse::success(
            $result,
            'Notification sent successfully.'
        );
    }
}
