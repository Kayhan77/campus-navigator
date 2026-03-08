<?php

declare(strict_types=1);

namespace App\Filters;

/**
 * Filter for the lost_items table.
 *
 * ─── Query parameters supported ──────────────────────────────────
 *
 *   ?q=wallet          Search in title, description, location
 *   ?status=lost|found Exact status filter
 *   ?location=gate     Partial match on location
 *   ?date_from=...     Items reported on or after this date
 *   ?date_to=...       Items reported on or before this date
 *   ?sort_by=created_at
 *   ?sort_dir=asc|desc
 *
 * Note: user_id filtering is applied at the controller level,
 * never exposed as a raw query parameter.
 */
final class LostFoundFilter extends QueryFilter
{
    protected array $searchable = ['title', 'description', 'location'];

    protected array $sortable = ['created_at', 'title', 'status'];

    protected array $allowedFilters = ['status', 'location'];

    protected array $dateFields = ['created_at'];

    protected string $defaultDateField = 'created_at';

    protected ?array $defaultSort = ['by' => 'created_at', 'dir' => 'desc'];

    // ─────────────────────────────────────────────────────────────

    /**
     * Exact status filter — accepts only 'lost' or 'found'.
     * Any other value is silently ignored.
     */
    public function status(string $value): void
    {
        if (in_array($value, ['lost', 'found'], true)) {
            $this->builder->where('status', $value);
        }
    }

    /**
     * Partial match on location.
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
