<?php

namespace App\Services\Announcement;

use App\DTOs\Announcement\CreateAnnouncementDTO;
use App\DTOs\Announcement\UpdateAnnouncementDTO;
use App\Models\Announcement;
use App\Services\SupabaseStorageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class AnnouncementService
{
        public function __construct(
            private readonly \App\Services\Notification\NotificationService $notificationService,
            private readonly SupabaseStorageService $supabaseStorage
        ) {}

    private const IMAGE_PATH = 'announcements';
    private const AUDIT_RELATIONS = [
        'createdBy:id,name,email',
        'updatedBy:id,name,email',
        'publishedBy:id,name,email',
    ];

    // -------------------------------------------------------------------------
    // Listings
    // -------------------------------------------------------------------------

    /**
     * List active announcements only, ordered by pinned first, then latest.
     */
    public function listActive()
    {
        return Announcement::query()
            ->where('is_active', true)
            ->with(self::AUDIT_RELATIONS)
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Admin-only paginated list of all announcements.
     */
    public function listAdminPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Announcement::query()->with(self::AUDIT_RELATIONS)->latest()->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Single record
    // -------------------------------------------------------------------------

    public function getById(Announcement $announcement): Announcement
    {
        return $announcement->load(self::AUDIT_RELATIONS);
    }

    // -------------------------------------------------------------------------
    // Writes
    // -------------------------------------------------------------------------

    /**
     * Create announcement with image upload.
     */
    public function create(CreateAnnouncementDTO $dto, int $actorId, ?UploadedFile $image = null): Announcement
    {
        $data = $dto->toArray();
        $data['created_by'] = $actorId;
        $data['updated_by'] = $actorId;

        if (($data['is_active'] ?? true) === true) {
            $data['published_by'] = $actorId;
            $data['published_at'] = $data['published_at'] ?? now()->toDateTimeString();
        }

        if ($image) {
            $data['image'] = $this->storeImage($image);
        }

        $announcement = Announcement::create($data);

        // Send and store notification via central NotificationService
        $this->notificationService->sendAndStoreNotification(
            title: 'New Announcement',
            message: $announcement->title,
            type: 'announcement',
            data: ['announcement_id' => (int) $announcement->id],
            senderId: $actorId
        );

        return $announcement->fresh(self::AUDIT_RELATIONS);
    }

    /**
     * Update announcement with optional image replacement.
     */
    public function update(Announcement $announcement, UpdateAnnouncementDTO $dto, int $actorId, ?UploadedFile $image = null): Announcement
    {
        $data = $dto->toArray();
        $wasActive = (bool) $announcement->is_active;
        $willBeActive = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : $wasActive;

        $data['updated_by'] = $actorId;

        if (! $wasActive && $willBeActive) {
            $data['published_by'] = $actorId;

            if (! array_key_exists('published_at', $data) || $data['published_at'] === null) {
                $data['published_at'] = now()->toDateTimeString();
            }
        }

        if ($wasActive && array_key_exists('is_active', $data) && $data['is_active'] === false) {
            $data['published_by'] = null;
            $data['published_at'] = null;
        }

        if ($willBeActive && $announcement->published_by === null && ! array_key_exists('published_by', $data)) {
            $data['published_by'] = $actorId;
        }

        // Handle image replacement
        if ($image) {
            // Delete old image if exists
            if ($announcement->image) {
                $this->deleteImage($announcement->image);
            }
            $data['image'] = $this->storeImage($image);
        }

        $announcement->update($data);
        return $announcement->fresh(self::AUDIT_RELATIONS);
    }

    /**
     * Delete announcement and its image.
     */
    public function delete(Announcement $announcement): void
    {
        // Delete image from storage
        if ($announcement->image) {
            $this->deleteImage($announcement->image);
        }

        $announcement->delete();
    }

    // -------------------------------------------------------------------------
    // Image handling
    // -------------------------------------------------------------------------

    /**
     * Store uploaded image and return relative path.
     */
    private function storeImage(UploadedFile $image): string
    {
        return $this->supabaseStorage->uploadImage($image, self::IMAGE_PATH);
    }

    /**
     * Delete image from storage safely.
     */
    private function deleteImage(string $imagePath): void
    {
        $this->supabaseStorage->delete($imagePath);
    }

    private function notifyUsers(string $title, string $body, array $data = []): void
    {
        // DEPRECATED: Use NotificationService::sendAndStoreNotification() instead.
        // This method is kept temporarily for backward compatibility but should not be called.
        // All notifications must be stored in the database via the central NotificationService.
    }
}
