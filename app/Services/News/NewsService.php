<?php

namespace App\Services\News;

use App\Models\News;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class NewsService
{
    private const IMAGE_DISK = 'public';
    private const IMAGE_PATH = 'news';

    public function listPublished()
    {
        return News::query()
            ->where('is_published', true)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data, ?UploadedFile $image = null): News
    {
        if ($image) {
            $data['image'] = $this->storeImage($image);
        }

        return News::create($data);
    }

    public function update(News $news, array $data, ?UploadedFile $image = null): News
    {
        if ($image) {
            if ($news->image) {
                $this->deleteImage($news->image);
            }

            $data['image'] = $this->storeImage($image);
        }

        $news->update($data);

        return $news->fresh();
    }

    public function delete(News $news): void
    {
        if ($news->image) {
            $this->deleteImage($news->image);
        }

        $news->delete();
    }

    private function storeImage(UploadedFile $image): string
    {
        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

        return Storage::disk(self::IMAGE_DISK)
            ->putFileAs(self::IMAGE_PATH, $image, $filename);
    }

    private function deleteImage(string $imagePath): void
    {
        $disk = Storage::disk(self::IMAGE_DISK);

        if ($disk->exists($imagePath)) {
            $disk->delete($imagePath);
        }
    }
}
