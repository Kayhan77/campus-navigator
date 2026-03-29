<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'building_id' => 'sometimes|exists:buildings,id',
            'room_number' => 'sometimes|string|max:50',
            'floor'       => 'sometimes|nullable|integer',
            'capacity'    => 'sometimes|integer|min:1',
            'type'        => 'sometimes|string|max:100',
        ];
    }
}
