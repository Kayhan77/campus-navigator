<?php

namespace App\Services;

use App\Models\Event;
use App\DTOs\EventData;
use App\Exceptions\ApiException;

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

    public function update(Event $event, EventData $data): Event
    {
        $event->update([
            'title' => $data->title,
            'description' => $data->description,
            'location' => $data->location,
            'start_time' => $data->start_time,
            'end_time' => $data->end_time,
        ]);
        return $event;
    }

    public function delete(Event $event): bool
    {
        return $event->delete();
    }

     public function getAll()
    {
        return Event::with('events')->get();
    }

    public function getById(int $id): Event
    {
        return Event::with('events')->findOrFail($id);
    }
}
