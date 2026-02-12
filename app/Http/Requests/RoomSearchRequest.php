<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // everyone authorized to search
    }

    public function rules(): array
    {
        return [
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'capacity'    => ['nullable', 'integer', 'min:1'],
            'room_number' => ['nullable', 'string', 'max:255'],
        ];
    }
}
