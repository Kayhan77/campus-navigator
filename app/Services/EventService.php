<?php

namespace App\Services;

use App\Models\Event;
use App\DTOs\Event\EventData;

class EventService
{
    public function create(EventData $data, int $userId): Event
    {
        return Event::create([
            'title' => $data->title,
            'description' => $data->description,
            'location' => $data->location,
            'start_time' => $data->start_time,
            'end_time' => $data->end_time,
            'created_by' => $userId,
        ]);
    }
}
