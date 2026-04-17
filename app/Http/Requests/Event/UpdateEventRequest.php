<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id'               => 'sometimes|nullable|exists:rooms,id',
            'title'                 => 'sometimes|string|max:255',
            'description'           => 'sometimes|nullable|string',
            'image'                 => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048',
            'location'              => 'sometimes|nullable|string|max:255',
            'location_override'     => 'sometimes|nullable|string|max:255',
            'start_time'            => 'sometimes|date',
            'end_time'              => 'sometimes|date|after_or_equal:start_time',
            'status'                => 'sometimes|in:draft,published,cancelled,completed',
            'is_public'             => 'sometimes|boolean',
            'max_attendees'         => 'sometimes|nullable|integer|min:1',
            'registration_required' => 'sometimes|boolean',
            'reminder_sent_at'      => 'sometimes|nullable|date',
        ];
    }
}
