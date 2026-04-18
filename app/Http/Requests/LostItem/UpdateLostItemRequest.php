<?php

namespace App\Http\Requests\LostItem;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLostItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // could add role checks if needed
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|nullable',
            'location' => 'sometimes|string|max:255|nullable',
            'status' => 'sometimes|in:lost,found',
            'image' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
