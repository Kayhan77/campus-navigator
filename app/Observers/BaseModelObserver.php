<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\Search\SearchCacheService;
use Illuminate\Database\Eloquent\Model;
/**
 * Base observer for all Eloquent models.
 *
 * Subclasses declare a single cache tag via {@see tag()} and receive
 * automatic, deduplicated cache invalidation on every write operation.
 * No business logic belongs here — only cache flushing.
 *
 * Usage:
 *
 *   final class EventObserver extends BaseModelObserver
 *   {
 *       protected function tag(): string { return CacheTags::EVENTS; }
 *   }
 */
abstract class BaseModelObserver
{
    public function __construct(
        private readonly SearchCacheService $cache
    ) {}

    /**
     * The Redis tag used to scope this model's cached results.
     * Return a {@see \App\Services\Cache\CacheTags} constant.
     */
    abstract protected function tag(): string;

    // ─── Eloquent lifecycle hooks ─────────────────────────────────────────────

    public function created(Model $model): void
    {
        $this->flush();
    }

    public function updated(Model $model): void
    {
        $this->flush();
    }

    public function deleted(Model $model): void
    {
        $this->flush();
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    /**
     * Single invalidation point — prevents duplicate cache-flush calls
     * if lifecycle hooks are ever extended.
     */
    private function flush(): void
    {
        $this->cache->invalidate($this->tag());
    }
}
