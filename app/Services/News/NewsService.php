<?php

namespace App\Services\News;

use App\DTOs\News\CreateNewsDTO;
use App\DTOs\News\UpdateNewsDTO;
use App\Models\News;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class NewsService
{
        public function __construct(
            private readonly FirebaseService $firebase
        ) {}

    private const IMAGE_DISK = 'public';
    private const IMAGE_PATH = 'news';

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
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Admin-only paginated list of all news.
     */
    public function listAdminPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return News::latest()->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Single record
    // -------------------------------------------------------------------------

    public function getById(News $news): News
    {
        return $news;
    }

    // -------------------------------------------------------------------------
    // Writes
    // -------------------------------------------------------------------------

    /**
     * Create news with image upload.
     */
    public function create(CreateNewsDTO $dto): News
    {
        $data = $dto->toArray();

        if ($dto->image !== null) {
            try {
                $data['image'] = $this->storeImage($dto->image);
            } catch (\Exception $e) {
                Log::warning('[News] Image upload failed during create', ['error' => $e->getMessage()]);
            }
        }

        $news = News::create($data);

        if ($news->is_published) {
            $this->notifyUsers(
                title: 'News Published',
                body: $news->title,
                data: ['type' => 'news', 'id' => (string) $news->id]
            );
        }

        return $news;
    }

    /**
     * Update news with optional image replacement.
     */
    public function update(News $news, UpdateNewsDTO $dto): News
    {
        $data = $dto->toArray();

        // Handle image replacement
        if ($dto->image !== null) {
            try {
                // Delete old image if exists
                if ($news->image && Storage::disk(self::IMAGE_DISK)->exists($news->image)) {
                    Storage::disk(self::IMAGE_DISK)->delete($news->image);
                }
                $data['image'] = $this->storeImage($dto->image);
            } catch (\Exception $e) {
                Log::warning('[News] Image upload failed during update', ['error' => $e->getMessage()]);
                unset($data['image']);
            }
        }

        $wasPublished = (bool) $news->is_published;

        $news->update($data);
        $updated = $news->fresh();

        if (! $wasPublished && $updated->is_published) {
            $this->notifyUsers(
                title: 'News Published',
                body: $updated->title,
                data: ['type' => 'news', 'id' => (string) $updated->id]
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
        if ($news->image && Storage::disk(self::IMAGE_DISK)->exists($news->image)) {
            Storage::disk(self::IMAGE_DISK)->delete($news->image);
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
        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

        return Storage::disk(self::IMAGE_DISK)
            ->putFileAs(self::IMAGE_PATH, $image, $filename);
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
