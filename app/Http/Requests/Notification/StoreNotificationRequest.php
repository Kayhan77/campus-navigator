<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'message'     => ['required', 'string', 'max:5000'],
            'target_role' => ['required', Rule::in(['student', 'admin', 'all'])],
        ];
    }
}
