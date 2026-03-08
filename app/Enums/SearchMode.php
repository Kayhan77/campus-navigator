<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Controls which search strategy applySearch() uses in QueryFilter.
 *
 * ┌─────────────┬────────────────────────────────────────────────────────────┐
 * │ Mode        │ Behaviour                                                  │
 * ├─────────────┼────────────────────────────────────────────────────────────┤
 * │ Like        │ Default. WHERE col LIKE '%term%' across $searchable cols.  │
 * │             │ Works on all databases with no extra setup.                │
 * │             │ Leading wildcard cannot use B-tree; use FULLTEXT indexes   │
 * │             │ (migration: add_performance_indexes) to accelerate.        │
 * ├─────────────┼────────────────────────────────────────────────────────────┤
 * │ FullText    │ MySQL/PostgreSQL FULLTEXT / GIN word-level index.          │
 * │             │ Override applyFullTextSearch() in the filter subclass.     │
 * │             │ Use: $table->whereFullText($cols, $term) (Laravel 10+).    │
 * ├─────────────┼────────────────────────────────────────────────────────────┤
 * │ Scout       │ Meilisearch / Algolia via Laravel Scout.                   │
 * │             │ Scout cannot be composed into an Eloquent builder — the    │
 * │             │ service layer must intercept and call Model::search($term) │
 * │             │ then pass the resulting IDs via whereIn() into the builder.│
 * │             │ The filter pipeline falls back to LIKE until the service   │
 * │             │ is updated to handle Scout results directly.               │
 * └─────────────┴────────────────────────────────────────────────────────────┘
 *
 * Usage in a filter subclass:
 *
 *   use App\Enums\SearchMode;
 *
 *   class EventFilter extends QueryFilter
 *   {
 *       protected SearchMode $searchMode = SearchMode::FullText;
 *
 *       // Override to use MySQL FULLTEXT:
 *       protected function applyFullTextSearch(string $term): void
 *       {
 *           $this->builder->whereFullText($this->searchable, $term);
 *       }
 *   }
 */
enum SearchMode: string
{
    /** Standard LIKE '%term%' — works everywhere, no index dependency. */
    case Like = 'like';

    /** Database FULLTEXT index search — MySQL InnoDB or PostgreSQL GIN. */
    case FullText = 'fulltext';

    /** Laravel Scout (Meilisearch / Algolia) — requires service-layer handling. */
    case Scout = 'scout';
}
