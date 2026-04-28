<?php

namespace App\Services\Search;

use App\Models\Building;
use App\Models\Event;
use App\Models\LostItem;
use App\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    private const MAX_PER_MODEL = 20;
    private const DEFAULT_PER_MODEL = 5;

    /**
     * Search across Events, Buildings, Rooms, and LostItems using
     * keyword-aware filtering and weighted relevance ranking.
     *
     * @return array{query: string, events: Collection, buildings: Collection, rooms: Collection, lost_items: Collection}
     */
    public function search(string $term, int $perModel = self::DEFAULT_PER_MODEL): array
    {
        $parsed = $this->parseSearchInput($term);
        $limit = min(max($perModel, 1), self::MAX_PER_MODEL);

        return [
            'query' => $parsed['normalized'],
            'events' => $this->searchEvents($parsed, $limit),
            'buildings' => $this->searchBuildings($parsed, $limit),
            'rooms' => $this->searchRooms($parsed, $limit),
            'lost_items' => $this->searchLostItems($parsed, $limit),
        ];
    }

    /**
     * Build autocomplete suggestions from all searchable models.
     */
    public function suggestions(string $term, int $limit = 5): Collection
    {
        $parsed = $this->parseSearchInput($term);
        $suggestLimit = min(max($limit, 1), 10);
        $needle = $this->lastSearchToken($term, $parsed['keywords']);

        if ($needle === '') {
            return collect();
        }

        $eventSuggestions = $this->eventSuggestions($needle, $suggestLimit);
        $buildingSuggestions = $this->buildingSuggestions($needle, $suggestLimit);
        $roomSuggestions = $this->roomSuggestions($needle, $suggestLimit);
        $lostItemSuggestions = $this->lostItemSuggestions($needle, $suggestLimit);

        return $eventSuggestions
            ->concat($buildingSuggestions)
            ->concat($roomSuggestions)
            ->concat($lostItemSuggestions)
            ->sort(function (array $left, array $right): int {
                $scoreOrder = $right['score'] <=> $left['score'];

                if ($scoreOrder !== 0) {
                    return $scoreOrder;
                }

                return strcasecmp($left['label'], $right['label']);
            })
            ->take($suggestLimit)
            ->values();
    }

    private function searchEvents(array $parsed, int $limit): Collection
    {
        $query = Event::query()
            ->with(['room:id,building_id,room_number', 'building:id,name'])
            ->select('events.*');

        $this->applyKeywordFilter($query, $parsed['keywords'], ['title', 'description', 'location']);

        [$scoreSql, $bindings] = $this->buildScoreSql(
            fullTerm: $parsed['normalized_lower'],
            keywords: $parsed['keywords'],
            primaryField: 'title',
            mediumFields: ['location'],
            lowFields: ['description'],
        );

        return $query
            ->selectRaw("{$scoreSql} AS relevance", $bindings)
            ->orderByDesc('relevance')
            ->orderBy('title')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    private function searchBuildings(array $parsed, int $limit): Collection
    {
        $query = Building::query()->select('buildings.*');

        $this->applyKeywordFilter($query, $parsed['keywords'], ['name', 'description']);

        [$scoreSql, $bindings] = $this->buildScoreSql(
            fullTerm: $parsed['normalized_lower'],
            keywords: $parsed['keywords'],
            primaryField: 'name',
            mediumFields: [],
            lowFields: ['description'],
        );

        return $query
            ->selectRaw("{$scoreSql} AS relevance", $bindings)
            ->orderByDesc('relevance')
            ->orderBy('name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    private function searchRooms(array $parsed, int $limit): Collection
    {
        $query = Room::query()
            ->with('building:id,name')
            ->select('rooms.*');

        $this->applyKeywordFilter(
            query: $query,
            keywords: $parsed['keywords'],
            columns: ['room_number', 'type'],
            relatedColumns: ['building' => ['name', 'description']],
        );

        [$scoreSql, $bindings] = $this->buildScoreSql(
            fullTerm: $parsed['normalized_lower'],
            keywords: $parsed['keywords'],
            primaryField: 'room_number',
            mediumFields: ['type'],
            lowFields: [],
        );

        return $query
            ->selectRaw("{$scoreSql} AS relevance", $bindings)
            ->orderByDesc('relevance')
            ->orderBy('room_number')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    private function searchLostItems(array $parsed, int $limit): Collection
    {
        $query = LostItem::query()
            ->with('user:id,name')
            ->select('lost_items.*');

        $this->applyKeywordFilter($query, $parsed['keywords'], ['title', 'description', 'location']);

        [$scoreSql, $bindings] = $this->buildScoreSql(
            fullTerm: $parsed['normalized_lower'],
            keywords: $parsed['keywords'],
            primaryField: 'title',
            mediumFields: ['location'],
            lowFields: ['description'],
        );

        return $query
            ->selectRaw("{$scoreSql} AS relevance", $bindings)
            ->orderByDesc('relevance')
            ->orderBy('title')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Ensure every meaningful keyword appears in at least one searchable field.
     * This produces AND between tokens and OR across columns for each token.
     */
    private function applyKeywordFilter(
        Builder $query,
        array $keywords,
        array $columns,
        array $relatedColumns = []
    ): void {
        $query->where(function (Builder $mustMatchAllKeywords) use ($keywords, $columns, $relatedColumns): void {
            foreach ($keywords as $keyword) {
                $pattern = $this->containsPattern($keyword);

                $mustMatchAllKeywords->where(function (Builder $perKeyword) use ($columns, $pattern, $relatedColumns): void {
                    $this->addWhereAnyColumnLike($perKeyword, $columns, $pattern);

                    foreach ($relatedColumns as $relation => $relationCols) {
                        $perKeyword->orWhereHas($relation, function (Builder $relationQuery) use ($relationCols, $pattern): void {
                            $relationQuery->where(function (Builder $nested) use ($relationCols, $pattern): void {
                                $this->addWhereAnyColumnLike($nested, $relationCols, $pattern);
                            });
                        });
                    }
                });
            }
        });
    }

    private function addWhereAnyColumnLike(Builder $query, array $columns, string $pattern): void
    {
        foreach ($columns as $index => $column) {
            if ($index === 0) {
                $query->where($column, 'like', $pattern);
                continue;
            }

            $query->orWhere($column, 'like', $pattern);
        }
    }

    /**
     * Build a weighted relevance expression.
     */
    private function buildScoreSql(
        string $fullTerm,
        array $keywords,
        string $primaryField,
        array $mediumFields,
        array $lowFields
    ): array {
        $weights = config('search.relevance_weights', []);

        $exactWeight = (int) ($weights['exact'] ?? 120);
        $startsWithWeight = (int) ($weights['starts_with'] ?? 80);
        $containsWeight = (int) ($weights['contains'] ?? 45);
        $keywordExactWeight = (int) ($weights['keyword_exact'] ?? 24);
        $keywordStartsWithWeight = (int) ($weights['keyword_starts_with'] ?? 14);
        $keywordContainsWeight = (int) ($weights['keyword_contains'] ?? 8);
        $mediumContainsWeight = (int) ($weights['medium_field_contains'] ?? 20);
        $lowContainsWeight = (int) ($weights['low_field_contains'] ?? 8);

        $sqlParts = [];
        $bindings = [];

        $sqlParts[] = "CASE WHEN LOWER({$primaryField}) = ? THEN ? ELSE 0 END";
        $bindings[] = $fullTerm;
        $bindings[] = $exactWeight;

        $sqlParts[] = "CASE WHEN LOWER({$primaryField}) LIKE ? THEN ? ELSE 0 END";
        $bindings[] = $this->startsWithPattern($fullTerm);
        $bindings[] = $startsWithWeight;

        $sqlParts[] = "CASE WHEN LOWER({$primaryField}) LIKE ? THEN ? ELSE 0 END";
        $bindings[] = $this->containsPattern($fullTerm);
        $bindings[] = $containsWeight;

        foreach ($keywords as $keyword) {
            $sqlParts[] = "CASE WHEN LOWER({$primaryField}) = ? THEN ? ELSE 0 END";
            $bindings[] = $keyword;
            $bindings[] = $keywordExactWeight;

            $sqlParts[] = "CASE WHEN LOWER({$primaryField}) LIKE ? THEN ? ELSE 0 END";
            $bindings[] = $this->startsWithPattern($keyword);
            $bindings[] = $keywordStartsWithWeight;

            $sqlParts[] = "CASE WHEN LOWER({$primaryField}) LIKE ? THEN ? ELSE 0 END";
            $bindings[] = $this->containsPattern($keyword);
            $bindings[] = $keywordContainsWeight;

            foreach ($mediumFields as $field) {
                $sqlParts[] = "CASE WHEN LOWER({$field}) LIKE ? THEN ? ELSE 0 END";
                $bindings[] = $this->containsPattern($keyword);
                $bindings[] = $mediumContainsWeight;
            }

            foreach ($lowFields as $field) {
                $sqlParts[] = "CASE WHEN LOWER({$field}) LIKE ? THEN ? ELSE 0 END";
                $bindings[] = $this->containsPattern($keyword);
                $bindings[] = $lowContainsWeight;
            }
        }

        return ['(' . implode(' + ', $sqlParts) . ')', $bindings];
    }

    private function eventSuggestions(string $needle, int $limit): Collection
    {
        return Event::query()
            ->select(['id', 'title'])
            ->selectRaw(
                'CASE
                    WHEN LOWER(title) = ? THEN 100
                    WHEN LOWER(title) LIKE ? THEN 75
                    WHEN LOWER(title) LIKE ? THEN 40
                    ELSE 0
                END AS score',
                [$needle, $this->startsWithPattern($needle), $this->containsPattern($needle)]
            )
            ->where(function (Builder $query) use ($needle): void {
                $query->whereRaw('LOWER(title) LIKE ?', [$this->startsWithPattern($needle)])
                    ->orWhereRaw('LOWER(title) LIKE ?', [$this->containsPattern($needle)]);
            })
            ->orderByDesc('score')
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(static fn (Event $item): array => [
                'type' => 'event',
                'id' => $item->id,
                'label' => $item->title,
                'score' => (int) ($item->score ?? 0),
            ]);
    }

    private function buildingSuggestions(string $needle, int $limit): Collection
    {
        return Building::query()
            ->select(['id', 'name'])
            ->selectRaw(
                'CASE
                    WHEN LOWER(name) = ? THEN 100
                    WHEN LOWER(name) LIKE ? THEN 75
                    WHEN LOWER(name) LIKE ? THEN 40
                    ELSE 0
                END AS score',
                [$needle, $this->startsWithPattern($needle), $this->containsPattern($needle)]
            )
            ->where(function (Builder $query) use ($needle): void {
                $query->whereRaw('LOWER(name) LIKE ?', [$this->startsWithPattern($needle)])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$this->containsPattern($needle)]);
            })
            ->orderByDesc('score')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(static fn (Building $item): array => [
                'type' => 'building',
                'id' => $item->id,
                'label' => $item->name,
                'score' => (int) ($item->score ?? 0),
            ]);
    }

    private function roomSuggestions(string $needle, int $limit): Collection
    {
        return Room::query()
            ->with('building:id,name')
            ->select(['id', 'room_number'])
            ->selectRaw(
                'CASE
                    WHEN LOWER(room_number) = ? THEN 100
                    WHEN LOWER(room_number) LIKE ? THEN 75
                    WHEN LOWER(room_number) LIKE ? THEN 40
                    ELSE 0
                END AS score',
                [$needle, $this->startsWithPattern($needle), $this->containsPattern($needle)]
            )
            ->where(function (Builder $query) use ($needle): void {
                $query->whereRaw('LOWER(room_number) LIKE ?', [$this->startsWithPattern($needle)])
                    ->orWhereRaw('LOWER(room_number) LIKE ?', [$this->containsPattern($needle)])
                    ->orWhereHas('building', function (Builder $buildingQuery) use ($needle): void {
                        $buildingQuery->whereRaw('LOWER(name) LIKE ?', [$this->startsWithPattern($needle)]);
                    });
            })
            ->orderByDesc('score')
            ->orderBy('room_number')
            ->limit($limit)
            ->get()
            ->map(static function (Room $item): array {
                $buildingName = $item->building?->name;
                $label = $buildingName !== null
                    ? "{$item->room_number} ({$buildingName})"
                    : $item->room_number;

                return [
                    'type' => 'room',
                    'id' => $item->id,
                    'label' => $label,
                    'score' => (int) ($item->score ?? 0),
                ];
            });
    }

    private function lostItemSuggestions(string $needle, int $limit): Collection
    {
        return LostItem::query()
            ->select(['id', 'title'])
            ->selectRaw(
                'CASE
                    WHEN LOWER(title) = ? THEN 100
                    WHEN LOWER(title) LIKE ? THEN 75
                    WHEN LOWER(title) LIKE ? THEN 40
                    ELSE 0
                END AS score',
                [$needle, $this->startsWithPattern($needle), $this->containsPattern($needle)]
            )
            ->where(function (Builder $query) use ($needle): void {
                $query->whereRaw('LOWER(title) LIKE ?', [$this->startsWithPattern($needle)])
                    ->orWhereRaw('LOWER(title) LIKE ?', [$this->containsPattern($needle)]);
            })
            ->orderByDesc('score')
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(static fn (LostItem $item): array => [
                'type' => 'lost_item',
                'id' => $item->id,
                'label' => $item->title,
                'score' => (int) ($item->score ?? 0),
            ]);
    }

    private function parseSearchInput(string $term): array
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $term) ?? '');
        $normalizedLower = mb_strtolower($normalized);

        $stopWords = collect((array) config('search.stop_words', ['of', 'the', 'and']))
            ->map(static fn (string $word): string => mb_strtolower(trim($word)))
            ->filter()
            ->values();

        $minKeywordLength = max((int) config('search.min_keyword_length', 2), 1);

        $tokens = collect(preg_split('/\s+/u', $normalizedLower, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(static fn (string $token): string => trim($token))
            ->filter(static fn (string $token): bool => mb_strlen($token) >= $minKeywordLength)
            ->reject(static fn (string $token) => $stopWords->contains($token))
            ->unique()
            ->values();

        if ($tokens->isEmpty() && $normalizedLower !== '') {
            $tokens = collect([$normalizedLower]);
        }

        return [
            'normalized' => $normalized,
            'normalized_lower' => $normalizedLower,
            'keywords' => $tokens->all(),
        ];
    }

    private function lastSearchToken(string $term, array $keywords): string
    {
        $trimmed = trim($term);

        if ($trimmed !== '') {
            $lastRawToken = collect(preg_split('/\s+/u', mb_strtolower($trimmed), -1, PREG_SPLIT_NO_EMPTY) ?: [])->last();
            if (is_string($lastRawToken) && $lastRawToken !== '') {
                $stopWords = collect((array) config('search.stop_words', ['of', 'the', 'and']))
                    ->map(static fn (string $word): string => mb_strtolower(trim($word)));

                if (! $stopWords->contains($lastRawToken)) {
                    return $lastRawToken;
                }
            }
        }

        return (string) (collect($keywords)->last() ?? '');
    }

    private function startsWithPattern(string $value): string
    {
        return $this->escapeLike($value) . '%';
    }

    private function containsPattern(string $value): string
    {
        return '%' . $this->escapeLike($value) . '%';
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
