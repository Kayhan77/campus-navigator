<?php

namespace App\Http\Resources\Api\V1;

use App\Services\SupabaseStorageService;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
{
    public function toArray($request): array
    {
        $createdBy = $this->createdBy;
        $updatedBy = $this->updatedBy;
        $publishedBy = $this->publishedBy;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'image' => SupabaseStorageService::publicUrl($this->image),
            'is_active' => (bool) $this->is_active,
            'is_pinned' => (bool) $this->is_pinned,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'created_by_id' => $this->created_by,
            'updated_by_id' => $this->updated_by,
            'published_by_id' => $this->published_by,
            'created_by' => $createdBy ? [
                'id' => $createdBy->id,
                'name' => $createdBy->name,
                'email' => $createdBy->email,
            ] : null,
            'updated_by' => $updatedBy ? [
                'id' => $updatedBy->id,
                'name' => $updatedBy->name,
                'email' => $updatedBy->email,
            ] : null,
            'published_by' => $publishedBy ? [
                'id' => $publishedBy->id,
                'name' => $publishedBy->name,
                'email' => $publishedBy->email,
            ] : null,
        ];
    }
}
