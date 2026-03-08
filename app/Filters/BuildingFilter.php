<?php

declare(strict_types=1);

namespace App\Filters;

/**
 * Filter for the buildings table.
 *
 * ─── Query parameters supported ──────────────────────────────────
 *
 *   ?q=main          Search in name, description
 *   ?sort_by=name    Order column
 *   ?sort_dir=asc|desc
 */
final class BuildingFilter extends QueryFilter
{
    protected array $searchable = ['name', 'description'];

    protected array $sortable = ['name', 'created_at'];

    protected array $allowedFilters = [];

    protected ?array $defaultSort = ['by' => 'name', 'dir' => 'asc'];
}
