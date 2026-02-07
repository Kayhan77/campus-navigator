<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LostItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'status' => $this->status,
            'user_id' => $this->user_id,
        ];
    }
}
