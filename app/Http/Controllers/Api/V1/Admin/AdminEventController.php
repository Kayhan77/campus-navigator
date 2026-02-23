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
use App\Services\Admin\AdminEventService;

class AdminEventController extends Controller
{
    public function __construct(
        private AdminEventService $service
    ) {}

    public function index()
    {
        $events = $this->service->listEvents()
            ->through(fn($event) => new EventResource($event));

        return ApiResponse::paginated($events, 'Events retrieved successfully.');
    }

    public function store(EventRequest $request)
    {
        $dto   = new CreateEventDTO($request->validated());
        $event = $this->service->create($dto, $request->user()->id);

        return ApiResponse::success(
            new EventResource($event),
            'Event created successfully.',
            201
        );
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $dto     = new UpdateEventDTO($request->validated());
        $updated = $this->service->update($event, $dto);

        return ApiResponse::success(
            new EventResource($updated),
            'Event updated successfully.'
        );
    }

    public function destroy(Event $event)
    {
        $this->service->delete($event);

        return ApiResponse::success(null, 'Event deleted successfully.');
    }
}
