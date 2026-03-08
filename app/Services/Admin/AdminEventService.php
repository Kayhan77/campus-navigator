<?php

namespace App\Services\Admin;

use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;
use App\Models\Event;
use App\Services\Event\EventService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @deprecated Merged into EventService.
 *
 * This class delegates every call to EventService and exists only to
 * avoid breaking any code that may still reference it during transition.
 * Migrate callers to EventService directly and remove this file.
 */
class AdminEventService
{
    public function __construct(
        private readonly EventService $service
    ) {}

    public function listEvents(int $perPage = 15): LengthAwarePaginator
    {
        return $this->service->listAdminPaginated($perPage);
    }

    public function create(CreateEventDTO $dto, int $actorId): Event
    {
        return $this->service->create($dto, $actorId);
    }

    public function update(Event $event, UpdateEventDTO $dto): Event
    {
        return $this->service->update($event, $dto);
    }

    public function delete(Event $event): void
    {
        $this->service->delete($event);
    }
}
