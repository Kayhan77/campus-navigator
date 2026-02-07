<?php

namespace App\Services;

use App\Models\User;
use App\DTOs\Auth\RegisterData;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(RegisterData $data): User
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'role' => $data->role,
        ]);
    }
}
