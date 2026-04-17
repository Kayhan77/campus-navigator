<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
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

    public function store(StoreAnnouncementRequest $request)
    {
        $announcement = $this->service->create($request->validated(), $request->file('image'));

        return ApiResponse::success(
            new AnnouncementResource($announcement),
            'Announcement created successfully.',
            201
        );
    }

    public function show(Announcement $announcement)
    {
        return ApiResponse::success(
            new AnnouncementResource($announcement),
            'Announcement retrieved successfully.'
        );
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement)
    {
        $updated = $this->service->update($announcement, $request->validated(), $request->file('image'));

        return ApiResponse::success(
            new AnnouncementResource($updated),
            'Announcement updated successfully.'
        );
    }

    public function destroy(Announcement $announcement)
    {
        $this->service->delete($announcement);

        return ApiResponse::success(null, 'Announcement deleted successfully.');
    }
}
