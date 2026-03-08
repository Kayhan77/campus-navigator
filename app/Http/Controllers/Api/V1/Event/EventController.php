<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;
use App\Filters\EventFilter;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\EventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Services\Event\EventService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(
        private readonly EventService $service
    ) {}

    public function index(Request $request, EventFilter $filter)
    {
        $paginator = $this->service->listPaginated($filter, $request, $this->resolvePerPage($request));

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator->getCollection()->transform(fn ($e) => new EventResource($e));

        return ApiResponse::paginated($paginator, 'Events retrieved successfully.');
    }

    public function show(Event $event)
    {
        return ApiResponse::success(
            new EventResource($this->service->getById($event)),
            'Event retrieved successfully.'
        );
    }

    // public function store(EventRequest $request)
    // {
    //     $this->authorize('create', Event::class);
    //     $dto   = new CreateEventDTO($request->validated());
    //     $event = $this->service->create($dto, $request->user()->id);
    //     return ApiResponse::success(new EventResource($event), 'Event created successfully.', 201);
    // }

    // public function update(UpdateEventRequest $request, Event $event)
    // {
    //     $this->authorize('update', $event);
    //     $dto     = new UpdateEventDTO($request->validated());
    //     $updated = $this->service->update($event, $dto);
    //     return ApiResponse::success(new EventResource($updated), 'Event updated successfully.');
    // }

    // public function destroy(Event $event)
    // {
    //     $this->authorize('delete', $event);
    //     $this->service->delete($event);
    //     return ApiResponse::success(null, 'Event deleted successfully.');
    // }
}
