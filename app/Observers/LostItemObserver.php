<?php

namespace App\Observers;

class LostItemObserver extends BaseSearchObserver
{
    protected function modelTag(): string
    {
        return 'lost_items';
    }
}
