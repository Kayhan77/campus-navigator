<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\EventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Services\Event\EventService;

class AdminEventController extends Controller
{
    public function __construct(
        private readonly EventService $service
    ) {}

    public function index()
    {
        $events = $this->service->listAdminPaginated()
            ->through(fn ($event) => new EventResource($event));

        return ApiResponse::paginated($events, 'Events retrieved successfully.');
    }

    public function show(Event $event)
    {
        return ApiResponse::success(
            new EventResource($this->service->getById($event)),
            'Event retrieved successfully.'
        );
    }

    public function store(EventRequest $request)
    {
        $dto   = CreateEventDTO::fromRequest($request);
        $event = $this->service->create($dto, $request->user()->id, $request->file('image'));

        return ApiResponse::success(new EventResource($event), 'Event created successfully.', 201);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $dto     = UpdateEventDTO::fromRequest($request);
        $updated = $this->service->update($event, $dto, $request->file('image'));

        return ApiResponse::success(new EventResource($updated), 'Event updated successfully.');
    }

    public function destroy(Event $event)
    {
        $this->service->delete($event);

        return ApiResponse::success(null, 'Event deleted successfully.');
    }
}