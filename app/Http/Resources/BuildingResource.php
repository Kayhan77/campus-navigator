<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BuildingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => $this->description,
            'rooms' => $this->whenLoaded('rooms')
        ];
    }
}
