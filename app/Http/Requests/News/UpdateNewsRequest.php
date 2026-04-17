<?php

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_published' => 'sometimes|boolean',
            'published_at' => 'sometimes|nullable|date',
        ];
    }
}
