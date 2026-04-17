<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\Announcement\CreateAnnouncementDTO;
use App\DTOs\Announcement\UpdateAnnouncementDTO;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use App\Http\Resources\Api\V1\AnnouncementResource;
use App\Models\Announcement;
use App\Services\Announcement\AnnouncementService;

class AdminAnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementService $service
    ) {}

    public function index()
    {
        $announcements = $this->service->listAdminPaginated()
            ->through(fn ($item) => new AnnouncementResource($item));

        return ApiResponse::paginated($announcements, 'Announcements retrieved successfully.');
    }

    public function show(Announcement $announcement)
    {
        return ApiResponse::success(
            new AnnouncementResource($this->service->getById($announcement)),
            'Announcement retrieved successfully.'
        );
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $this->authorize('create', Announcement::class);
        $dto             = CreateAnnouncementDTO::fromRequest($request);
        $announcement = $this->service->create($dto, $request->file('image'));

        return ApiResponse::success(new AnnouncementResource($announcement), 'Announcement created successfully.', 201);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement)
    {
        $this->authorize('update', $announcement);
        $dto     = UpdateAnnouncementDTO::fromRequest($request);
        $updated = $this->service->update($announcement, $dto, $request->file('image'));

        return ApiResponse::success(new AnnouncementResource($updated), 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $this->authorize('delete', $announcement);
        $this->service->delete($announcement);

        return ApiResponse::success(null, 'Announcement deleted successfully.');
    }
}
