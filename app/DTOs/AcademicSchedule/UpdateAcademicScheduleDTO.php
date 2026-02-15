<?php
namespace App\DTOs\AcademicSchedule;

class UpdateAcademicScheduleDTO
{
    public ?string $course_name;
    public ?string $day;
    public ?string $start_time;
    public ?string $end_time;
    public ?int $room_id;

    public function __construct(array $data)
    {
        $this->course_name = $data['course_name'] ?? null;
        $this->day = $data['day'] ?? null;
        $this->start_time = $data['start_time'] ?? null;
        $this->end_time = $data['end_time'] ?? null;
        $this->room_id = $data['room_id'] ?? null;
    }

    public function toArray(): array
    {
        return array_filter([
            'course_name' => $this->course_name,
            'day' => $this->day,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'room_id' => $this->room_id,
        ], fn($value) => $value !== null);
    }
}
