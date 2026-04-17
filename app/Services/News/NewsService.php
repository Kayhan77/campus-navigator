<?php

namespace App\Services\News;

use App\DTOs\News\CreateNewsDTO;
use App\DTOs\News\UpdateNewsDTO;
use App\Models\News;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class NewsService
{
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
    public function create(CreateNewsDTO $dto, ?UploadedFile $image = null): News
    {
        $data = $dto->toArray();

        if ($image) {
            $data['image'] = $this->storeImage($image);
        }

        return News::create($data);
    }

    /**
     * Update news with optional image replacement.
     */
    public function update(News $news, UpdateNewsDTO $dto, ?UploadedFile $image = null): News
    {
        $data = $dto->toArray();

        // Handle image replacement
        if ($image) {
            // Delete old image if exists
            if ($news->image) {
                $this->deleteImage($news->image);
            }
            $data['image'] = $this->storeImage($image);
        }

        $news->update($data);
        return $news->fresh();
    }

    /**
     * Delete news and its image.
     */
    public function delete(News $news): void
    {
        // Delete image from storage
        if ($news->image) {
            $this->deleteImage($news->image);
        }

        $news->delete();
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
