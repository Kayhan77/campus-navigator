<?php

namespace App\Services\Event;

use App\Models\Event;
use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;
use Illuminate\Support\Facades\Cache;


class EventService
{
    public function create(CreateEventDTO $data, int $userId): Event
    {
        $event = Event::create($data->toArray($userId));
        Cache::forget('events'); 
        return $event;
    }

    public function update(Event $event, UpdateEventDTO $data): Event
    {
        $event->update($data->toArray());
        Cache::forget('events'); 
        return $event;
    }

    public function delete(Event $event): bool
    {
        $deleted = $event->delete();
        Cache::forget('events'); 
        return $deleted;
    }

    public function getAll()
    {
        return Cache::remember('events', 60, function () {
            return Event::latest()->paginate(10);
        });
    }

    public function getById(Event $event): Event
    {
        return $event;
    }
}
