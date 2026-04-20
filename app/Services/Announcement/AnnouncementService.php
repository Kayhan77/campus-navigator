<?php

namespace App\Services\Announcement;

use App\DTOs\Announcement\CreateAnnouncementDTO;
use App\DTOs\Announcement\UpdateAnnouncementDTO;
use App\Models\Announcement;
use App\Models\User;
use App\Services\FirebaseService;
use App\Services\SupabaseStorageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class AnnouncementService
{
        public function __construct(
            private readonly FirebaseService $firebase,
            private readonly SupabaseStorageService $supabaseStorage
        ) {}

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

        $announcement = Announcement::create($data);

        $this->notifyUsers(
            title: 'New Announcement',
            body: $announcement->title,
            data: ['type' => 'announcement', 'id' => (string) $announcement->id]
        );

        return $announcement;
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
        User::query()
            ->whereHas('deviceTokens')
            ->with('deviceTokens:id,user_id,token')
            ->chunkById(200, function ($users) use ($title, $body, $data): void {
                foreach ($users as $user) {
                    $this->firebase->sendToUser($user, $title, $body, $data);
                }
            });
    }
}
