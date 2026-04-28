<?php

namespace App\Services\News;

use App\DTOs\News\CreateNewsDTO;
use App\DTOs\News\UpdateNewsDTO;
use App\Models\News;
use App\Services\SupabaseStorageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class NewsService
{
        public function __construct(
            private readonly \App\Services\Notification\NotificationService $notificationService,
            private readonly SupabaseStorageService $supabaseStorage
        ) {}

    private const IMAGE_PATH = 'news';
    private const AUDIT_RELATIONS = [
        'createdBy:id,name,email',
        'updatedBy:id,name,email',
        'publishedBy:id,name,email',
    ];

    // -------------------------------------------------------------------------
    // Listings
    // -------------------------------------------------------------------------

    /**
     * List published news only, ordered by latest first.
     */
    public function listPublished()
    {
        return News::query()
            ->where('is_published', true)
            ->with(self::AUDIT_RELATIONS)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Admin-only paginated list of all news.
     */
    public function listAdminPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return News::query()->with(self::AUDIT_RELATIONS)->latest()->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Single record
    // -------------------------------------------------------------------------

    public function getById(News $news): News
    {
        return $news->load(self::AUDIT_RELATIONS);
    }

    // -------------------------------------------------------------------------
    // Writes
    // -------------------------------------------------------------------------

    /**
     * Create news with image upload.
     */
    public function create(CreateNewsDTO $dto, int $actorId): News
    {
        $data = $dto->toArray();
        $data['created_by'] = $actorId;
        $data['updated_by'] = $actorId;

        if (($data['is_published'] ?? true) === true) {
            $data['published_by'] = $actorId;
            $data['published_at'] = $data['published_at'] ?? now()->toDateTimeString();
        }

        if ($dto->image !== null) {
            try {
                $data['image'] = $this->storeImage($dto->image);
            } catch (\Exception $e) {
                Log::warning('[News] Image upload failed during create', ['error' => $e->getMessage()]);
            }
        }

        $news = News::create($data);

        if ($news->is_published) {
            // Send and store notification via central NotificationService
            $this->notificationService->sendAndStoreNotification(
                title: 'News Published',
                message: $news->title,
                type: 'news',
                data: ['news_id' => (int) $news->id],
                senderId: $actorId
            );
        }

        return $news->fresh(self::AUDIT_RELATIONS);
    }

    /**
     * Update news with optional image replacement.
     */
    public function update(News $news, UpdateNewsDTO $dto, int $actorId): News
    {
        $data = $dto->toArray();

        // Handle image replacement
        if ($dto->image !== null) {
            try {
                // Delete old image if exists
                if ($news->image) {
                    $this->supabaseStorage->delete($news->image);
                }
                $data['image'] = $this->storeImage($dto->image);
            } catch (\Exception $e) {
                Log::warning('[News] Image upload failed during update', ['error' => $e->getMessage()]);
                unset($data['image']);
            }
        }

        $wasPublished = (bool) $news->is_published;
        $willBePublished = array_key_exists('is_published', $data)
            ? (bool) $data['is_published']
            : $wasPublished;

        $data['updated_by'] = $actorId;

        if (! $wasPublished && $willBePublished) {
            $data['published_by'] = $actorId;

            if (! array_key_exists('published_at', $data) || $data['published_at'] === null) {
                $data['published_at'] = now()->toDateTimeString();
            }
        }

        if ($wasPublished && array_key_exists('is_published', $data) && $data['is_published'] === false) {
            $data['published_by'] = null;
            $data['published_at'] = null;
        }

        if ($willBePublished && $news->published_by === null && ! array_key_exists('published_by', $data)) {
            $data['published_by'] = $actorId;
        }

        $news->update($data);
        $updated = $news->fresh(self::AUDIT_RELATIONS);

        if (! $wasPublished && $updated->is_published) {
            // Send and store notification via central NotificationService
            $this->notificationService->sendAndStoreNotification(
                title: 'News Published',
                message: $updated->title,
                type: 'news',
                data: ['news_id' => (int) $updated->id],
                senderId: $actorId
            );
        }

        return $updated;
    }

    /**
     * Delete news and its image.
     */
    public function delete(News $news): void
    {
        // Delete image from storage
        if ($news->image) {
            $this->supabaseStorage->delete($news->image);
        }

        $news->delete();
    }

    // -------------------------------------------------------------------------
    // Image handling
    // -------------------------------------------------------------------------

    /**
     * Store uploaded image and return relative path.
     * Throws exception on failure (caught by caller).
     */
    private function storeImage(UploadedFile $image): string
    {
        return $this->supabaseStorage->uploadImage($image, self::IMAGE_PATH);
    }

    private function notifyUsers(string $title, string $body, array $data = []): void
    {
        // DEPRECATED: Use NotificationService::sendAndStoreNotification() instead.
        // This method is kept temporarily for backward compatibility but should not be called.
        // All notifications must be stored in the database via the central NotificationService.
    }
}
