<?php

namespace App\Filters;

/**
 * Filter for the academic_schedules table.
 *
 * ─── Query parameters supported ──────────────────────────────────
 *
 *   ?q=physics         Search in course_name
 *   ?day=Monday        Exact match on day of week
 *   ?room_id=5         Filter by room
 *   ?sort_by=day|course_name|start_time
 *   ?sort_dir=asc|desc
 */
class AcademicScheduleFilter extends QueryFilter
{
    protected array $searchable = ['course_name'];

    protected array $sortable = ['course_name', 'day', 'start_time', 'created_at'];

    protected array $allowedFilters = ['day', 'room_id'];

    protected ?array $defaultSort = ['by' => 'day', 'dir' => 'asc'];

    // ─────────────────────────────────────────────────────────────

    /**
     * Exact match on day name.
     * Only recognises standard English day names.
     */
    public function day(string $value): void
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $normalised = ucfirst(strtolower(trim($value)));

        if (in_array($normalised, $days, true)) {
            $this->builder->where('day', $normalised);
        }
    }
}
