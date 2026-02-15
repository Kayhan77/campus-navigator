<?php

namespace App\Services\Event;

use App\Models\Event;
use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;

class EventService
{
    public function create(CreateEventDTO $data, int $userId): Event
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
        return Event::all();
    }

    public function getById(int $id): Event
    {
        return Event::findOrFail($id);
    }
}
