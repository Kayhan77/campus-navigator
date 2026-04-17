<?php

namespace App\Http\Requests\Announcement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnouncementRequest extends FormRequest
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
            'is_active' => 'sometimes|boolean',
            'is_pinned' => 'sometimes|boolean',
            'published_at' => 'sometimes|nullable|date',
        ];
    }
}
