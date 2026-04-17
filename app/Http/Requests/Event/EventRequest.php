<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id'               => 'nullable|exists:rooms,id',
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string',
            'image'                 => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'location'              => 'nullable|string|max:255',
            'location_override'     => 'nullable|string|max:255',
            'start_time'            => 'required|date',
            'end_time'              => 'required|date|after_or_equal:start_time',
            'status'                => 'sometimes|in:draft,published,cancelled,completed',
            'is_public'             => 'boolean',
            'max_attendees'         => 'nullable|integer|min:1',
            'registration_required' => 'boolean',
            'reminder_sent_at'      => 'nullable|date',
        ];
    }
}
