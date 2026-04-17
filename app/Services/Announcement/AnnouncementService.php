<?php

namespace App\Services\Announcement;

use App\Models\Announcement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AnnouncementService
{
    private const IMAGE_DISK = 'public';
    private const IMAGE_PATH = 'announcements';

    public function listActive()
    {
        return Announcement::query()
            ->where('is_active', true)
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data, ?UploadedFile $image = null): Announcement
    {
        if ($image) {
            $data['image'] = $this->storeImage($image);
        }

        return Announcement::create($data);
    }

    public function update(Announcement $announcement, array $data, ?UploadedFile $image = null): Announcement
    {
        if ($image) {
            if ($announcement->image) {
                $this->deleteImage($announcement->image);
            }

            $data['image'] = $this->storeImage($image);
        }

        $announcement->update($data);

        return $announcement->fresh();
    }

    public function delete(Announcement $announcement): void
    {
        if ($announcement->image) {
            $this->deleteImage($announcement->image);
        }

        $announcement->delete();
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
