<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AcademicScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'course_name' => $this->course_name,
            'day' => $this->day,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'room' => [
                'id' => $this->room->id,
                'room_number' => $this->room->room_number,
                'floor' => $this->room->floor,
                'building' => [
                    'id' => $this->room->building->id,
                    'name' => $this->room->building->name,
                ],
            ],
        ];
    }
}
