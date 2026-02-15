<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\DTOs\Event\CreateEventDTO;
use App\DTOs\Event\UpdateEventDTO;
use App\Services\Event\EventService;
use App\Http\Requests\Event\EventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class EventController extends Controller
{
    protected EventService $service;

    public function __construct(EventService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $events = $this->service->getAll();
        return response()->json(EventResource::collection($events));
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json(new EventResource($this->service->getById($event->id)));
    }

    public function store(EventRequest $request): JsonResponse
    {
        $this->authorize('create', Event::class);
        $data = new CreateEventDTO($request->validated());
        $event = $this->service->create($data, $request->user()->id);
        return response()->json(new EventResource($event), 201);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);
        $data = new UpdateEventDTO($request->validated());
        $event = $this->service->update($event, $data);
        return response()->json(new EventResource($event));
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);
        $this->service->delete($event);
        return response()->json(['message' => 'Event deleted successfully'], 200);
    }
}
