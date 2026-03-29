<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'building_id'  => $this->building_id,
            'room_number'  => $this->room_number,
            'floor'        => $this->floor,
            'capacity'     => $this->capacity,
            'type'         => $this->type,
            'building'     => $this->whenLoaded('building', function () {
                return [
                    'id'   => $this->building->id,
                    'name' => $this->building->name,
                ];
            }),
            'events_count' => $this->whenCounted('events'),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
