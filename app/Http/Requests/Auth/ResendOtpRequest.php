<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'exists:pending_registrations,email',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'No pending registration found for this email address.',
        ];
    }
}
