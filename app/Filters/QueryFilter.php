<?php

namespace App\Filters;

use App\Enums\SearchMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Abstract base for all model-specific query filters.
 *
 * Subclasses define whitelisted columns and optional custom filter handlers.
 */
abstract class QueryFilter
{
    protected Builder $builder;

    /**
     * Columns searched by ?q= / ?search=.
     *
     * @var string[]
     */
    protected array $searchable = [];

    /**
     * Columns allowed for ORDER BY.
     *
     * @var string[]
     */
    protected array $sortable = [];

    /**
     * Allowed request keys to process as filters.
     *
     * @var string[]
     */
    protected array $allowedFilters = [];

    /**
     * Date fields allowed for ?date_field=.
     *
     * @var string[]
     */
    protected array $dateFields = [];

    protected string $defaultDateField = 'created_at';

    /**
     * @var array{by: string, dir: 'asc'|'desc'}|null
     */
    protected ?array $defaultSort = ['by' => 'created_at', 'dir' => 'desc'];

    protected SearchMode $searchMode = SearchMode::Like;

    /**
     * Relations always eager-loaded by this filter.
     *
     * @var string[]
     */
    protected array $with = [];

    private const RESERVED = [
        'page',
        'per_page',
        'sort_by',
        'sort_dir',
        'q',
        'search',
        'no_cache',
        'date_from',
        'date_to',
        'date_field',
        'include',
    ];

    public function __construct(protected readonly Request $request)
    {
    }

    final public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        $this->applyEagerLoads();
        $this->applySearch();
        $this->applyAllowedFilters();
        $this->applyDateRange();
        $this->applySort();

        return $this->builder;
    }

    private function applyEagerLoads(): void
    {
        if (! empty($this->with)) {
            $this->builder->with($this->with);
        }
    }

    protected function applySearch(): void
    {
        $raw = $this->request->input('q') ?? $this->request->input('search');

        if (blank($raw) || empty($this->searchable)) {
            return;
        }

        $term = $this->sanitizeTerm((string) $raw);
        $min = (int) config('search.min_search_length', 2);
        $max = (int) config('search.max_search_length', 100);

        if (mb_strlen($term) < $min || mb_strlen($term) > $max) {
            return;
        }

        match ($this->searchMode) {
            SearchMode::FullText => $this->applyFullTextSearch($term),
            SearchMode::Scout => $this->applyScoutSearch($term),
            default => $this->applyLikeSearch($term),
        };
    }

    protected function applyLikeSearch(string $term): void
    {
        $escaped = '%' . $this->escapeLike(mb_strtolower($term)) . '%';

        $this->builder->where(function (Builder $query) use ($escaped): void {
            foreach ($this->searchable as $column) {
                $query->orWhereRaw('LOWER(' . $query->getQuery()->grammar->wrap($column) . ') LIKE ?', [$escaped]);
            }
        });
    }

    protected function applyFullTextSearch(string $term): void
    {
        $this->applyLikeSearch($term);
    }

    protected function applyScoutSearch(string $term): void
    {
        $this->applyLikeSearch($term);
    }

    protected function applyAllowedFilters(): void
    {
        foreach ($this->allowedFilters as $param) {
            if (in_array($param, self::RESERVED, true)) {
                continue;
            }

            $value = $this->request->input($param);

            if ($value === null || $value === '') {
                continue;
            }

            if (method_exists($this, $param)) {
                $this->$param($value);
            } else {
                $this->builder->where($param, $value);
            }
        }
    }

    protected function applyDateRange(): void
    {
        $from = $this->request->input('date_from');
        $to = $this->request->input('date_to');

        if (! $from && ! $to) {
            return;
        }

        $allowed = empty($this->dateFields) ? $this->sortable : $this->dateFields;
        $field = $this->request->input('date_field', $this->defaultDateField);

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

    protected function applySort(): void
    {
        $sortBy = $this->request->input('sort_by');
        $sortDir = strtolower((string) $this->request->input('sort_dir', 'asc'));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc';

        if ($sortBy && in_array($sortBy, $this->sortable, true)) {
            $this->builder->orderBy($sortBy, $sortDir);

            return;
        }

        if ($this->defaultSort !== null) {
            $this->builder->orderBy(
                $this->defaultSort['by'],
                $this->defaultSort['dir'] ?? 'desc'
            );
        }
    }

    protected function escapeLike(string $value): string
    {
        return addcslashes(trim($value), '%_\\');
    }

    private function sanitizeTerm(string $term): string
    {
        return (string) preg_replace('/\s+/', ' ', trim($term));
    }

    public function searchMode(): SearchMode
    {
        return $this->searchMode;
    }

    public function toCacheParameters(): array
    {
        $params = $this->request->only(
            array_merge(
                ['q', 'search', 'sort_by', 'sort_dir', 'date_from', 'date_to', 'date_field'],
                $this->allowedFilters
            )
        );

        $params['_search_mode'] = $this->searchMode->value;

        return $params;
    }
}