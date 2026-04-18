<?php
namespace App\Http\Requests\Building;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:building,department,parking,cafeteria,reception,office,mosque',
            'category' => 'sometimes|string|max:100|nullable',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'description' => 'sometimes|string|nullable',
            'opening_hours' => 'sometimes|string|max:255|nullable',
            'notes' => 'sometimes|string|nullable',
            'image' => 'sometimes|nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
