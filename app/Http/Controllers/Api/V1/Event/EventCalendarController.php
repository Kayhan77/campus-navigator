<?php

namespace App\Http\Controllers\Api\V1\Event;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Http\Controllers\Controller;

class EventCalendarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $events = Event::all();

        // Transform for frontend calendar
        $calendarEvents = $events->map(function ($event) {
            return [
                'title' => $event->title,
                'start' => $event->start_time->toDateTimeString(),
                'end'   => $event->end_time->toDateTimeString(),
            ];
        });

        return response()->json($calendarEvents);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
