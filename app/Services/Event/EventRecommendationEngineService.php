<?php

namespace App\Services\Event;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EventRecommendationEngineService
{
    /**
     * Rank and return top 3 recommendations from the provided events only.
     *
     * @param array<int, array<string, mixed>> $events
     * @return array<string, mixed>
     */
    public function recommend(string $query, array $events): array
    {
        $tokens = $this->queryTokens($query);

        if ($tokens->isEmpty() || empty($events)) {
            return [
                'recommendations' => [],
            ];
        }

        $ranked = collect($events)
            ->filter(static fn (mixed $event): bool => is_array($event))
            ->map(function (array $event) use ($tokens): array {
                $score = $this->scoreEvent($event, $tokens);

                return [
                    'title' => (string) Arr::get($event, 'title', ''),
                    'reason' => $this->buildReason($event, $tokens),
                    'score' => $score,
                ];
            })
            ->filter(static fn (array $item): bool => $item['title'] !== '' && $item['score'] > 0)
            ->sort(function (array $left, array $right): int {
                $byScore = $right['score'] <=> $left['score'];

                if ($byScore !== 0) {
                    return $byScore;
                }

                return strcasecmp($left['title'], $right['title']);
            })
            ->take(3)
            ->values();

        if ($ranked->isEmpty()) {
            return [
                'recommendations' => [],
            ];
        }

        return [
            'recommendations' => $ranked->map(static fn (array $item): array => [
                'title' => $item['title'],
                'reason' => $item['reason'],
            ])->all(),
        ];
    }

    private function scoreEvent(array $event, Collection $tokens): int
    {
        $score = 0;

        $category = $this->normalizeText(Arr::get($event, 'category'));
        $title = $this->normalizeText(Arr::get($event, 'title'));
        $description = $this->normalizeText(Arr::get($event, 'description'));

        $tags = $this->normalizeTags(Arr::get($event, 'tags'));

        foreach ($tokens as $token) {
            if ($category !== '') {
                if ($category === $token) {
                    $score += 40;
                } elseif (str_contains($category, $token)) {
                    $score += 25;
                }
            }

            foreach ($tags as $tag) {
                if ($tag === $token) {
                    $score += 30;
                    continue;
                }

                if (str_contains($tag, $token)) {
                    $score += 18;
                }
            }

            if ($title !== '') {
                if ($title === $token) {
                    $score += 30;
                } elseif (str_starts_with($title, $token)) {
                    $score += 24;
                } elseif (str_contains($title, $token)) {
                    $score += 18;
                }
            }

            if ($description !== '' && str_contains($description, $token)) {
                $score += 10;
            }
        }

        $queryPhrase = $tokens->implode(' ');

        if ($queryPhrase !== '' && $title !== '' && str_contains($title, $queryPhrase)) {
            $score += 20;
        }

        if ($queryPhrase !== '' && $description !== '' && str_contains($description, $queryPhrase)) {
            $score += 12;
        }

        return $score;
    }

    private function buildReason(array $event, Collection $tokens): string
    {
        $matchedParts = [];

        $category = $this->normalizeText(Arr::get($event, 'category'));
        if ($category !== '' && $this->containsAnyToken($category, $tokens)) {
            $matchedParts[] = 'category';
        }

        $tags = $this->normalizeTags(Arr::get($event, 'tags'));
        if ($tags->isNotEmpty()) {
            $tagMatched = $tags->contains(fn (string $tag): bool => $this->containsAnyToken($tag, $tokens));
            if ($tagMatched) {
                $matchedParts[] = 'tags';
            }
        }

        $title = $this->normalizeText(Arr::get($event, 'title'));
        if ($title !== '' && $this->containsAnyToken($title, $tokens)) {
            $matchedParts[] = 'title';
        }

        $description = $this->normalizeText(Arr::get($event, 'description'));
        if ($description !== '' && $this->containsAnyToken($description, $tokens)) {
            $matchedParts[] = 'description';
        }

        $matchedParts = array_values(array_unique($matchedParts));

        if (count($matchedParts) === 0) {
            return 'Matches user intent based on available event fields.';
        }

        return 'Matches user intent through ' . implode(', ', $matchedParts) . '.';
    }

    private function containsAnyToken(string $text, Collection $tokens): bool
    {
        foreach ($tokens as $token) {
            if (str_contains($text, $token)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeTags(mixed $tags): Collection
    {
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }

        if (! is_array($tags)) {
            return collect();
        }

        return collect($tags)
            ->map(fn (mixed $tag): string => $this->normalizeText($tag))
            ->filter(static fn (string $tag): bool => $tag !== '')
            ->values();
    }

    private function queryTokens(string $query): Collection
    {
        $stopWords = ['of', 'the', 'and', 'a', 'an', 'in', 'on', 'to', 'for'];

        return collect(preg_split('/\s+/u', $this->normalizeText($query), -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(static fn (string $token): string => trim($token))
            ->filter(static fn (string $token): bool => mb_strlen($token) >= 2)
            ->reject(static fn (string $token): bool => in_array($token, $stopWords, true))
            ->unique()
            ->values();
    }

    private function normalizeText(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return mb_strtolower($normalized);
    }
}
