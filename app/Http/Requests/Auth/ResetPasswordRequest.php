<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [
                'token' => 'required',
                'email' => 'required|email|exists:users,email',
                'password' => 'required|string|min:8|confirmed',
        ];
        // return [
        //     'token' => 'required|string',
        //     'email' => 'required|email|exists:users,email',
        //     'password' => ['required', 'confirmed', Rules\Password::defaults()],
        // ];
    }
}
