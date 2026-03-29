<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'title'                 => $this->title,
            'description'           => $this->description,
            'location'              => $this->location,
            'location_override'     => $this->location_override,
            'status'                => $this->status,
            'is_public'             => (bool) $this->is_public,
            'max_attendees'         => $this->max_attendees,
            'registration_required' => (bool) $this->registration_required,
            'reminder_sent_at'      => $this->reminder_sent_at?->toISOString(),
            'start_time'            => $this->start_time?->toISOString(),
            'end_time'              => $this->end_time?->toISOString(),
            'room'                  => new RoomResource($this->whenLoaded('room')),
            'building'              => new BuildingResource($this->whenLoaded('building')),
            'created_by'            => $this->created_by,
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
