<?php

namespace App\Observers;

class EventObserver extends BaseSearchObserver
{
    protected function modelTag(): string
    {
        return 'events';
    }
}
