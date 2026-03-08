<?php

declare(strict_types=1);

namespace App\Filters;

use App\Enums\SearchMode;
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
 *
 * ─── Search mode ─────────────────────────────────────────────────
 *
 *   Default: SearchMode::Like  (LIKE '%term%').
 *   Switch to SearchMode::FullText and override applyFullTextSearch()
 *   once the FULLTEXT index from the performance migration is confirmed
 *   in production:
 *
 *       protected SearchMode $searchMode = SearchMode::FullText;
 *
 *       protected function applyFullTextSearch(string $term): void
 *       {
 *           $this->builder->whereFullText($this->searchable, $term);
 *       }
 *
 * ─── N+1 prevention ──────────────────────────────────────────────
 *
 *   Events are owned by a user (created_by). If EventResource renders
 *   the creator name, add 'creator' to $with:
 *
 *       protected array $with = ['creator'];
 */
final class EventFilter extends QueryFilter
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
     * Partial LIKE match on location.
     * Uses escapeLike() so user input like "%Hall%" stays literal.
     */
    public function location(string $value): void
    {
        $this->builder->where(
            'location',
            'LIKE',
            '%' . $this->escapeLike($value) . '%'
        );
    }
}
