<?php

namespace App\Filters;

/**
 * Filter for the rooms table.
 *
 * ─── Query parameters supported ──────────────────────────────────
 *
 *   ?q=101           Search in room_number
 *   ?building_id=3   Filter rooms belonging to a specific building
 *   ?floor=2         Exact match on floor number
 *   ?sort_by=room_number
 *   ?sort_dir=asc|desc
 */
class RoomFilter extends QueryFilter
{
    protected array $searchable = ['room_number'];

    protected array $sortable = ['room_number', 'floor', 'created_at'];

    protected array $allowedFilters = ['building_id', 'floor'];

    protected ?array $defaultSort = ['by' => 'room_number', 'dir' => 'asc'];
}
