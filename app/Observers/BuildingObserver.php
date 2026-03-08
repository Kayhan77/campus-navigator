<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\Cache\CacheTags;

final class BuildingObserver extends BaseModelObserver
{
    protected function tag(): string
    {
        return CacheTags::BUILDINGS;
    }
}
