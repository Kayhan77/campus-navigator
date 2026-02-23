<?php

namespace App\Services\Admin;

use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;
use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminEventService
{
    public function listEvents(int $perPage = 15): LengthAwarePaginator
    {
        return Event::with('room')->latest()->paginate($perPage);
    }

    public function create(CreateEventDTO $dto, int $actorId): Event
    {
        return Event::create($dto->toArray($actorId));
    }

    public function update(Event $event, UpdateEventDTO $dto): Event
    {
        $event->update($dto->toArray());
        return $event->fresh();
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }
}
