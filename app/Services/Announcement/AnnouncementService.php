<?php

namespace App\Services\Announcement;

use App\DTOs\Announcement\CreateAnnouncementDTO;
use App\DTOs\Announcement\UpdateAnnouncementDTO;
use App\Models\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AnnouncementService
{
    private const IMAGE_DISK = 'public';
    private const IMAGE_PATH = 'announcements';

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
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Admin-only paginated list of all announcements.
     */
    public function listAdminPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Announcement::latest()->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Single record
    // -------------------------------------------------------------------------

    public function getById(Announcement $announcement): Announcement
    {
        return $announcement;
    }

    // -------------------------------------------------------------------------
    // Writes
    // -------------------------------------------------------------------------

    /**
     * Create announcement with image upload.
     */
    public function create(CreateAnnouncementDTO $dto, ?UploadedFile $image = null): Announcement
    {
        $data = $dto->toArray();

        if ($image) {
            $data['image'] = $this->storeImage($image);
        }

        return Announcement::create($data);
    }

    /**
     * Update announcement with optional image replacement.
     */
    public function update(Announcement $announcement, UpdateAnnouncementDTO $dto, ?UploadedFile $image = null): Announcement
    {
        $data = $dto->toArray();

        // Handle image replacement
        if ($image) {
            // Delete old image if exists
            if ($announcement->image) {
                $this->deleteImage($announcement->image);
            }
            $data['image'] = $this->storeImage($image);
        }

        $announcement->update($data);
        return $announcement->fresh();
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
        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

        return Storage::disk(self::IMAGE_DISK)
            ->putFileAs(self::IMAGE_PATH, $image, $filename);
    }

    /**
     * Delete image from storage safely.
     */
    private function deleteImage(string $imagePath): void
    {
        $disk = Storage::disk(self::IMAGE_DISK);

        if ($disk->exists($imagePath)) {
            $disk->delete($imagePath);
        }
    }
}
