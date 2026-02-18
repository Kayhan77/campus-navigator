<?php


namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreRegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true; // allow all requests here
    }

    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email'),                // not registered yet
                Rule::unique('pending_registrations', 'email') // not already pre-registered
            ],
            'password' => 'required|string|min:6|confirmed',
        ];
    }

    public function messages()
    {
        return [
            'email.unique' => 'This email is already registered or pending registration.',
            'password.confirmed' => 'Password confirmation does not match.'
        ];
    }
}
