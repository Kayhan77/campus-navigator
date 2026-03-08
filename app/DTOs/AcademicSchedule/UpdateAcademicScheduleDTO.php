<?php

declare(strict_types=1);

namespace App\DTOs\AcademicSchedule;

use App\Http\Requests\AcademicSchedule\UpdateAcademicScheduleRequest;

final class UpdateAcademicScheduleDTO
{
    public function __construct(
        public readonly ?string $course_name,
        public readonly ?string $day,
        public readonly ?string $start_time,
        public readonly ?string $end_time,
        public readonly ?int    $room_id,
    ) {}

    public static function fromRequest(UpdateAcademicScheduleRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            course_name: $validated['course_name'] ?? null,
            day:         $validated['day'] ?? null,
            start_time:  $validated['start_time'] ?? null,
            end_time:    $validated['end_time'] ?? null,
            room_id:     isset($validated['room_id']) ? (int) $validated['room_id'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'course_name' => $this->course_name,
            'day'         => $this->day,
            'start_time'  => $this->start_time,
            'end_time'    => $this->end_time,
            'room_id'     => $this->room_id,
        ], fn(mixed $value): bool => $value !== null);
    }
}
