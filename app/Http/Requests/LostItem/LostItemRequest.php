<?php

namespace App\Http\Requests\LostItem;

use Illuminate\Foundation\Http\FormRequest;

class LostItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:lost,found',
        ];
    }
}
