<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AnnouncementResource;
use App\Models\Announcement;
use App\Services\Announcement\AnnouncementService;

class AnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementService $service
    ) {}

    public function index()
    {
        $announcements = $this->service->listActive();

        return ApiResponse::success(
            AnnouncementResource::collection($announcements),
            'Announcements retrieved successfully.'
        );
    }

    public function show(Announcement $announcement)
    {
        return ApiResponse::success(
            new AnnouncementResource($this->service->getById($announcement)),
            'Announcement retrieved successfully.'
        );
    }
}
