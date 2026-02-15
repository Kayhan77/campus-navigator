<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'room' => new RoomResource($this->whenLoaded('room')),
            'building' => new BuildingResource($this->whenLoaded('building')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'created_by' => $this->created_by,
        ];
    }
}
