<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Abstract base for all model-specific query filters.
 *
 * Each subclass declares the columns it accepts and Laravel's DI
 * resolves the Request automatically when the filter is type-hinted
 * in a controller method.
 *
 * ─── Usage in a controller ───────────────────────────────────────
 *
 *   public function index(Request $request, EventFilter $filter)
 *   {
 *       $paginator = Event::filter($filter)->paginate(15);
 *       …
 *   }
 *
 * ─── Supported query parameters ──────────────────────────────────
 *
 *   ?q=campus          Full-text search across $searchable columns
 *   ?search=campus     Alias for q
 *
 *   ?sort_by=title     Sort column  (must be in $sortable whitelist)
 *   ?sort_dir=asc|desc Sort direction   (default: asc)
 *
 *   ?date_from=2024-01-01   Date range start (inclusive)
 *   ?date_to=2024-12-31     Date range end   (inclusive)
 *   ?date_field=start_time  Which date column to filter on
 *                           (must be in $dateFields whitelist)
 *
 *   Any key in $allowedFilters is additionally processed.
 *   If the subclass defines a method with that name it is called;
 *   otherwise an exact WHERE match is applied automatically.
 *
 *   ?page=2 ?per_page=20 ?no_cache=1  → handled externally, ignored here
 */
abstract class QueryFilter
{
    protected Builder $builder;

    /**
     * Columns to search on ?q= / ?search=.
     * Override in each subclass.
     *
     * @var string[]
     */
    protected array $searchable = [];

    /**
     * Columns available for ORDER BY.
     * ONLY columns listed here may be sorted – prevents injection.
     *
     * @var string[]
     */
    protected array $sortable = [];

    /**
     * Request keys that may be applied as filters.
     * If a method with the same name exists in the subclass it is called;
     * otherwise an exact WHERE match is applied.
     *
     * @var string[]
     */
    protected array $allowedFilters = [];

    /**
     * Columns that may be used as the ?date_field= target.
     * Defaults to $sortable when empty.
     *
     * @var string[]
     */
    protected array $dateFields = [];

    /**
     * Default date column when ?date_field is absent.
     */
    protected string $defaultDateField = 'created_at';

    /**
     * Default ORDER BY applied when no ?sort_by is present.
     * Set to null to apply no default ordering.
     *
     * @var array{by: string, dir: 'asc'|'desc'}|null
     */
    protected ?array $defaultSort = ['by' => 'created_at', 'dir' => 'desc'];

    // ─── Parameters the filter itself must never process ─────────────────────
    private const RESERVED = [
        'page', 'per_page', 'sort_by', 'sort_dir',
        'q', 'search', 'no_cache',
        'date_from', 'date_to', 'date_field', 'include', // handled by scopeWithAllowed — never a filter key
    ];

    public function __construct(protected Request $request) {}

    // =========================================================================
    // Entry point
    // =========================================================================

    /**
     * Apply all active filters to the builder and return it.
     * Called by the Filterable::scopeFilter() model scope.
     */
    final public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        $this->applySearch();
        $this->applyAllowedFilters();
        $this->applyDateRange();
        $this->applySort();

        return $this->builder;
    }

    // =========================================================================
    // Filter stages
    // =========================================================================

    /**
     * Full-text LIKE search across all $searchable columns.
     *
     * Uses addcslashes to escape LIKE wildcards in the term so user
     * input like "50% off" doesn't become an open wildcard.
     */
    protected function applySearch(): void
    {
        $term = $this->request->input('q') ?? $this->request->input('search');

        if (blank($term) || empty($this->searchable)) {
            return;
        }

        $minLength = config('search.min_search_length', 2);

        if (mb_strlen($term) < $minLength) {
            return;
        }

        $escaped = '%' . addcslashes(trim($term), '%_\\') . '%';

        $this->builder->where(function (Builder $q) use ($escaped) {
            foreach ($this->searchable as $column) {
                $q->orWhere($column, 'LIKE', $escaped);
            }
        });
    }

    /**
     * Process each key in $allowedFilters.
     *
     * Security rules:
     *  - Only keys explicitly listed in $allowedFilters are processed.
     *  - Reserved parameter names are always skipped.
     *  - If the subclass defines a method for a key, it is called with
     *    the sanitised value; otherwise an exact WHERE is applied.
     *  - Empty and null values are silently ignored.
     */
    protected function applyAllowedFilters(): void
    {
        foreach ($this->allowedFilters as $param) {
            // Never call reserved keys or methods not tied to this param list
            if (in_array($param, self::RESERVED, true)) {
                continue;
            }

            $value = $this->request->input($param);

            if ($value === null || $value === '') {
                continue;
            }

            // Subclass has a dedicated method → delegate (e.g. status(), location())
            if (method_exists($this, $param)) {
                $this->$param($value);
            } else {
                // Default: safe parameterised exact match
                $this->builder->where($param, $value);
            }
        }
    }

    /**
     * Date range filter — ?date_from= and/or ?date_to=
     * The column used is validated against the $dateFields whitelist.
     */
    protected function applyDateRange(): void
    {
        $from  = $this->request->input('date_from');
        $to    = $this->request->input('date_to');

        if (! $from && ! $to) {
            return;
        }

        $allowed = empty($this->dateFields)
            ? $this->sortable
            : $this->dateFields;

        $field = $this->request->input('date_field', $this->defaultDateField);

        // Silently ignore if the requested date_field is not whitelisted
        if (! in_array($field, $allowed, true)) {
            return;
        }

        if ($from) {
            $this->builder->whereDate($field, '>=', $from);
        }

        if ($to) {
            $this->builder->whereDate($field, '<=', $to);
        }
    }

    /**
     * Apply ORDER BY — column is strictly validated against $sortable.
     * Falls back to $defaultSort when no valid sort_by is provided.
     */
    protected function applySort(): void
    {
        $sortBy  = $this->request->input('sort_by');
        $sortDir = strtolower((string) $this->request->input('sort_dir', 'asc'));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc';

        if ($sortBy && in_array($sortBy, $this->sortable, true)) {
            $this->builder->orderBy($sortBy, $sortDir);
            return;
        }

        // Fallback to default ordering
        if ($this->defaultSort !== null) {
            $this->builder->orderBy(
                $this->defaultSort['by'],
                $this->defaultSort['dir'] ?? 'desc'
            );
        }
    }

    // =========================================================================
    // Accessors for the cache service
    // =========================================================================

    /**
     * Return current raw request inputs relevant to this filter,
     * used by SearchCacheService to build a deterministic cache key.
     */
    public function toCacheParameters(): array
    {
        return $this->request->only(
            array_merge(
                ['q', 'search', 'sort_by', 'sort_dir', 'date_from', 'date_to', 'date_field'],
                $this->allowedFilters
            )
        );
    }
}
