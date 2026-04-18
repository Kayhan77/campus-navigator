<?php

namespace App\Http\Requests\Building;

use Illuminate\Foundation\Http\FormRequest;

class BuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:building,department,parking,cafeteria,reception,office,mosque',
            'category' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'description' => 'nullable|string',
            'opening_hours' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
