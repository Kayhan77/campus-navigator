<?php

namespace App\Services\Event;

use App\Models\Event;
use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;

class EventService
{
    public function create(CreateEventDTO $data, int $userId): Event
    {
        return Event::create($data->toArray($userId));
    }

    public function update(Event $event, UpdateEventDTO $data): Event
    {
        $event->update($data->toArray());
        return $event;
    }

    public function delete(Event $event): bool
    {
        return $event->delete();
    }

    public function getAll()
    {
        return Event::latest()->paginate(10);
    }

    public function getById(Event $event): Event
    {
        return $event;
    }
}
