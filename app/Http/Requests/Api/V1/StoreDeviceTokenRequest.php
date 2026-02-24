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
            // FCM tokens are typically 152-163 chars; enforce a sane range.
            'token'    => ['required', 'string', 'min:20', 'max:512'],
            'platform' => ['required', 'string', 'in:android,ios,web'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'  => 'A device token is required.',
            'token.min'       => 'Device token appears too short to be a valid FCM token.',
            'token.max'       => 'Device token must not exceed 512 characters.',
            'platform.required' => 'Platform is required (android, ios, or web).',
            'platform.in'     => 'Platform must be one of: android, ios, web.',
        ];
    }
}
