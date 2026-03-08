<?php

declare(strict_types=1);

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Extend {@see BaseModelObserver} directly instead.
 *
 * Kept as a compatibility shim so any external code that still
 * type-hints BaseSearchObserver does not break. Remove once all
 * usages have been migrated.
 */
abstract class BaseSearchObserver extends BaseModelObserver
{
    /**
     * @deprecated Implement {@see BaseModelObserver::tag()} instead.
     */
    protected function modelTag(): string
    {
        return $this->tag();
    }
}