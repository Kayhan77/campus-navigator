<?php

namespace App\Http\Resources\Api\V1;

use App\Services\SupabaseStorageService;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray($request): array
    {
        // registered_users_count is now a persisted database column (not a count aggregate)
        $registeredUsersCount = (int) ($this->registered_users_count ?? 0);
        $isFull = $this->max_attendees !== null
            ? $registeredUsersCount >= (int) $this->max_attendees
            : false;

        return [
            'id'                    => $this->id,
            'title'                 => $this->title,
            'description'           => $this->description,
            'image'                 => SupabaseStorageService::publicUrl($this->image),
            'location'              => $this->location,
            'location_override'     => $this->location_override,
            'status'                => $this->status,
            'is_public'             => (bool) $this->is_public,
            'max_attendees'         => $this->max_attendees,
            'registered_users_count' => $registeredUsersCount,
            'is_full'               => $isFull,
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
