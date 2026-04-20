<?php

namespace App\Http\Resources\Api\V1;

use App\Services\SupabaseStorageService;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => $this->description,
            'image' => SupabaseStorageService::publicUrl($this->image),
            'opening_hours' => $this->opening_hours,
            'notes' => $this->notes,
            'rooms_count' => $this->rooms_count ?? null,
            'rooms' => RoomResource::collection($this->whenLoaded('rooms')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
