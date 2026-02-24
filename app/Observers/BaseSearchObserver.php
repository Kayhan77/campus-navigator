<?php

namespace App\Observers;

use App\Services\Search\SearchCacheService;
use Illuminate\Database\Eloquent\Model;

/**
 * Base observer that invalidates the model's search cache tag
 * on any write operation.
 *
 * Concrete observers extend this class and set
 * protected string $modelTag = 'events';
 */
abstract class BaseSearchObserver
{
    public function __construct(
        protected readonly SearchCacheService $cache
    ) {}

    /**
     * The Redis tag used for this model's cached search results.
     * Subclasses MUST override this.
     */
    abstract protected function modelTag(): string;

    // ─── Eloquent lifecycle hooks ─────────────────────────────────────────────

    public function created(Model $model): void
    {
        $this->cache->invalidate($this->modelTag());
    }

    public function updated(Model $model): void
    {
        $this->cache->invalidate($this->modelTag());
    }

    public function deleted(Model $model): void
    {
        $this->cache->invalidate($this->modelTag());
    }

    public function restored(Model $model): void
    {
        $this->cache->invalidate($this->modelTag());
    }
}
