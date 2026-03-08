<?php

declare(strict_types=1);

namespace App\DTOs\AcademicSchedule;

use App\Http\Requests\AcademicSchedule\AcademicScheduleRequest;

final class CreateAcademicScheduleDTO
{
    public function __construct(
        public readonly string $course_name,
        public readonly string $day,
        public readonly string $start_time,
        public readonly string $end_time,
        public readonly int    $room_id,
    ) {}

    public static function fromRequest(AcademicScheduleRequest $request): self
    {
        return new self(
            course_name: $request->validated('course_name'),
            day:         $request->validated('day'),
            start_time:  $request->validated('start_time'),
            end_time:    $request->validated('end_time'),
            room_id:     (int) $request->validated('room_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'course_name' => $this->course_name,
            'day'         => $this->day,
            'start_time'  => $this->start_time,
            'end_time'    => $this->end_time,
            'room_id'     => $this->room_id,
        ];
    }
}
