<?php

namespace App\Observers;

class RoomObserver extends BaseSearchObserver
{
    protected function modelTag(): string
    {
        return 'rooms';
    }
}
