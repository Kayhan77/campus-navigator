<?php

namespace App\Observers;

class BuildingObserver extends BaseSearchObserver
{
    protected function modelTag(): string
    {
        return 'buildings';
    }
}
