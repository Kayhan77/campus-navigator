<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'building_id' => $this->building_id,
            'room_number' => $this->room_number,
            'floor' => $this->floor,
        ];
    }
}
