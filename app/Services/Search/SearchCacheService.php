<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Enums\UserRole;
use App\Filters\QueryFilter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Redis-backed cache service for paginated search results.
 *
 * Design decisions:
 *
 *  1. Cache TAGGING  (Redis / Memcached)
 *     Tags = [config('search.cache_tag_prefix'), $modelTag]
 *     One flush per tag clears every page / filter combination
 *     for that model without scanning all keys.
 *
 *  2. Cache KEY = SHA-256 of (model + sorted parameters + page)
 *     Keeps Redis key length constant and avoids URL-injection in keys.
 *
 *  3. Admin no-cache bypass
 *     Admins may pass ?no_cache=1 to skip the cache and see fresh data.
 *     The result is NOT stored back into the cache.
 *
 *  4. Graceful degradation
 *     Any cache exception is caught and logged; the callback is executed
 *     directly so the request never fails because of a Redis outage.
 */
class SearchCacheService
{
    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Return a cached value or execute the callback and cache it.
     *
     * @param string   $modelTag  Identifier used as a Redis tag (e.g. 'events')
     * @param string   $cacheKey  Pre-built deterministic cache key
     * @param callable $callback  Produces the value when the cache is cold
     * @param int|null $ttl       Override TTL in seconds (null = config default)
     * @return mixed
     */
    public function remember(
        string   $modelTag,
        string   $cacheKey,
        callable $callback,
        ?int     $ttl = null
    ): mixed {
        // Admin bypass: skip both reading and writing the cache
        if ($this->shouldBypassCache()) {
            return $callback();
        }

        $ttl = $ttl ?? config('search.cache_ttl', 300);

        // TTL of 0 means caching is disabled globally
        if ($ttl <= 0) {
            return $callback();
        }

        try {
            return $this->cacheStore($modelTag)
                ->remember($cacheKey, $ttl, $callback);
        } catch (\Throwable $e) {
            // Cache unavailable — degrade gracefully, never fail the request
            Log::warning('[SearchCache] Cache miss due to exception — fetching from DB', [
                'model' => $modelTag,
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    /**
     * Invalidate all cached search results for a model.
     * Called by model observers on any write operation (create/update/delete).
     *
     * @param string $modelTag  e.g. 'events', 'buildings'
     */
    public function invalidate(string $modelTag): void
    {
        if (! $this->supportsTags()) {
            // Non-tagging drivers cannot be selectively flushed.
            // Log and skip rather than flushing the whole cache.
            Log::debug('[SearchCache] Tag-based invalidation not available for driver: '
                . config('cache.default'));
            return;
        }

        try {
            Cache::tags($this->tags($modelTag))->flush();

            Log::debug('[SearchCache] Cache invalidated', ['model' => $modelTag]);
        } catch (\Throwable $e) {
            Log::warning('[SearchCache] Failed to invalidate cache', [
                'model' => $modelTag,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // Cache key builder (static — called by controllers)
    // =========================================================================

    /**
     * Build a stable, fixed-length cache key from:
     *  - model name
     *  - active filter parameters (from QueryFilter)
     *  - current page
     *  - per_page value
     *  - optional user ID (for user-scoped queries)
     *
     * Parameters are sorted before hashing so ?a=1&b=2 and ?b=2&a=1
     * produce the same key.
     */
    public static function buildKey(
        string      $model,
        QueryFilter $filter,
        int         $page    = 1,
        int         $perPage = 15,
        ?int        $userId  = null,
    ): string {
        $params = $filter->toCacheParameters();
        ksort($params);

        return 'search:' . hash('sha256', json_encode([
            'model'    => $model,
            'params'   => $params,
            'page'     => $page,
            'per_page' => $perPage,
            'user_id'  => $userId,
        ]));
    }

    /**
     * Build a stable cache key for non-paginated, non-filtered queries
     * (e.g. calendar views, upcoming-event windows, dashboard stats).
     *
     * $params should describe every dimension that makes the result unique
     * (e.g. ['range' => '24h'], ['view' => 'calendar'], ['user_id' => 5]).
     * Keys are sorted before hashing so parameter order never matters.
     *
     * Prefix is 'cache:' to distinguish from 'search:' paged results.
     */
    public static function buildSimpleKey(string $model, array $params = []): string
    {
        ksort($params);

        return 'cache:' . hash('sha256', json_encode([
            'model'  => $model,
            'params' => $params,
        ]));
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    /**
     * Return a cache store bound to the model tag when tags are supported,
     * or the default store otherwise.
     */
    private function cacheStore(string $modelTag): \Illuminate\Contracts\Cache\Repository
    {
        if ($this->supportsTags()) {
            return Cache::tags($this->tags($modelTag));
        }

        return Cache::store();
    }

    /**
     * An array of tags applied to every cache entry for the model.
     */
    private function tags(string $modelTag): array
    {
        $prefix = config('search.cache_tag_prefix', 'search');

        return [$prefix, "{$prefix}:{$modelTag}"];
    }

    /**
     * Whether the current cache driver supports tagging.
     * Redis and Memcached do; file and array drivers do not.
     */
    private function supportsTags(): bool
    {
        return in_array(config('cache.default'), ['redis', 'memcached'], true);
    }

    /**
     * Returns true when the cache should be bypassed.
     *
     * Condition: request has ?no_cache=1 AND
     *   - config('search.admin_no_cache') is enabled AND
     *   - the requesting user is an admin or super_admin
     */
    private function shouldBypassCache(): bool
    {
        if (! config('search.admin_no_cache', true)) {
            return false;
        }

        if (! request()->boolean('no_cache')) {
            return false;
        }

        $user = request()->user();

        return $user && $user->hasAnyRole(UserRole::adminRoles());
    }
}
