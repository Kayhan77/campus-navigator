<?php

namespace App\Http\Resources\Api\V1;

use App\Services\SupabaseStorageService;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'image' => SupabaseStorageService::publicUrl($this->image),
            'is_active' => (bool) $this->is_active,
            'is_pinned' => (bool) $this->is_pinned,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
