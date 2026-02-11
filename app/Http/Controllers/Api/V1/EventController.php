<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\EventData;
use App\Services\EventService;
use App\Http\Requests\EventRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class EventController extends Controller
{
    protected EventService $service;

    public function __construct(EventService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        return response()->json(EventResource::collection(Event::all()));
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json(new EventResource($event));
    }

    public function store(EventRequest $request): JsonResponse
    {
        $data = new EventData($request->validated());
        $event = $this->service->create($data, $request->user()->id);
        return response()->json(new EventResource($event), 201);
    }

    public function update(EventRequest $request, Event $event): JsonResponse
    {
        $data = new EventData($request->validated());
        $event = $this->service->update($event, $data);
        return response()->json(new EventResource($event));
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->service->delete($event);
        return response()->json(['message' => 'Event deleted successfully'], 200);
    }
}
