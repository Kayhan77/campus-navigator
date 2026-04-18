<?php

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_published' => 'sometimes|boolean',
            'published_at' => 'nullable|date',
        ];
    }
}
