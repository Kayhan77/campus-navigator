<?php

namespace App\DTOs\AcademicSchedule;

class CreateAcademicScheduleDTO
{
    public function __construct(
        public string $course_name,
        public string $day,
        public string $start_time,
        public string $end_time,
        public int $room_id
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            $request->course_name,
            $request->day,
            $request->start_time,
            $request->end_time,
            $request->room_id
        );
    }

    public function toArray(): array
    {
        return [
            'course_name' => $this->course_name,
            'day' => $this->day,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'room_id' => $this->room_id,
        ];
    }
}
