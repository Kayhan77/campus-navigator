<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Filter for the events table.
 *
 * ─── Query parameters supported ──────────────────────────────────
 *
 *   ?q=campus              Search in title, description, location
 *   ?location=Hall+A       Partial match on location
 *   ?date_from=2026-01-01  Events starting on or after this date
 *   ?date_to=2026-06-30    Events starting on or before this date
 *   ?date_field=start_time Which date column (start_time|end_time|created_at)
 *   ?sort_by=start_time    Order column
 *   ?sort_dir=asc|desc
 */
class EventFilter extends QueryFilter
{
    protected array $searchable = ['title', 'description', 'location'];

    protected array $sortable = [
        'created_at',
        'start_time',
        'end_time',
        'title',
    ];

    protected array $allowedFilters = ['location'];

    protected array $dateFields = ['start_time', 'end_time', 'created_at'];

    protected string $defaultDateField = 'start_time';

    protected ?array $defaultSort = ['by' => 'start_time', 'dir' => 'asc'];

    // ─────────────────────────────────────────────────────────────
    // Custom filter methods
    // ─────────────────────────────────────────────────────────────

    /**
     * Partial match on location (more useful than an exact match).
     */
    public function location(string $value): void
    {
        $this->builder->where(
            'location',
            'LIKE',
            '%' . addcslashes(trim($value), '%_\\') . '%'
        );
    }
}
