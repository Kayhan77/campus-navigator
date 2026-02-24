<?php

namespace App\Filters;

/**
 * Filter for the users table — used on admin endpoints only.
 *
 * ─── Query parameters supported ──────────────────────────────────
 *
 *   ?q=john            Search in name, email
 *   ?role=admin|user   Exact role filter
 *   ?is_verified=1|0   Filter by verification status
 *   ?date_from=...     Registered on or after
 *   ?date_to=...       Registered on or before
 *   ?sort_by=name|email|created_at
 *   ?sort_dir=asc|desc
 *
 * Security: password, remember_token, and any other hidden fields
 * are NOT in $searchable or $allowedFilters and can never be queried.
 */
class UserFilter extends QueryFilter
{
    protected array $searchable = ['name', 'email'];

    protected array $sortable = ['name', 'email', 'created_at', 'role'];

    protected array $allowedFilters = ['role', 'is_verified'];

    protected array $dateFields = ['created_at'];

    protected string $defaultDateField = 'created_at';

    protected ?array $defaultSort = ['by' => 'created_at', 'dir' => 'desc'];

    // ─────────────────────────────────────────────────────────────

    /**
     * Exact role filter — accepts only known roles.
     */
    public function role(string $value): void
    {
        if (in_array($value, ['user', 'admin', 'super_admin'], true)) {
            $this->builder->where('role', $value);
        }
    }

    /**
     * Boolean verification status filter.
     */
    public function is_verified(string $value): void
    {
        $this->builder->where('is_verified', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
}
