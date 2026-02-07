<?php

namespace App\Http\Controllers;

use App\DTOs\Event\EventData;
use App\Services\EventService;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;

class EventController extends Controller
{
    protected EventService $service;

    public function __construct(EventService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return EventResource::collection(Event::all());
    }

    public function store(EventRequest $request)
    {
        $data = new EventData($request->validated());
        $event = $this->service->create($data, $request->user()->id);
        return new EventResource($event);
    }
}
