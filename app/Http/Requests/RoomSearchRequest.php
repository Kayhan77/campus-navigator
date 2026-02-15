<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomSearchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'building_id' => 'nullable|exists:buildings,id',
            'room_number' => 'nullable|string|max:50',
            'floor' => 'nullable|integer',
        ];
    }
}
