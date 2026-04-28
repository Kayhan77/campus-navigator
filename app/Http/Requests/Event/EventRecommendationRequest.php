<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class EventRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_query' => ['required', 'string', 'min:2', 'max:200'],
            'events' => ['required', 'array'],
            'events.*.title' => ['required', 'string', 'max:255'],
            'events.*.category' => ['nullable', 'string', 'max:120'],
            'events.*.description' => ['nullable', 'string', 'max:2000'],
            'events.*.tags' => ['nullable'],
        ];
    }

    public function userQuery(): string
    {
        return (string) $this->input('user_query');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function events(): array
    {
        $events = $this->input('events', []);

        return is_array($events) ? $events : [];
    }
}
