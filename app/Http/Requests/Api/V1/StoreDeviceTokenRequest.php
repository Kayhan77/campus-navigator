<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only authenticated users can register device tokens
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'  => 'A device token is required.',
            'token.max'       => 'Device token must not exceed 255 characters.',
            'platform.in'     => 'Platform must be one of: android, ios, web.',
        ];
    }
}
