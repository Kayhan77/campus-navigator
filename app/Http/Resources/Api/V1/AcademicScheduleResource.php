<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AcademicScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'course_name' => $this->course_name,
            'day' => $this->day,
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'room' => new RoomResource($this->whenLoaded('room')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
