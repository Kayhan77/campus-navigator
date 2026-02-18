<?php

namespace App\Services\Auth;

use App\Models\PendingRegistration;
use App\Models\User;
use App\DTOs\Auth\RegisterPendingDTO;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Notifications\VerifyEmailNotification;


class PreRegisterService
{
    public function preRegister(RegisterPendingDTO $dto): PendingRegistration
    {
        $pending =   PendingRegistration::create([
            'name'       => $dto->name,
            'email'      => $dto->email,
            'password'   => Hash::make($dto->password),
            'token'      => Str::random(64),
            'expires_at' => now()->addHours(24),
        ]);
        $pending->notify(new VerifyEmailNotification($pending->token));
        return $pending;
    }

    public function verify(string $token): User
    {   
        $pending = PendingRegistration::where('token', $token)->first();

        if (!$pending) {
            throw new \Exception('Invalid verification token.');
        }

        if ($pending->expires_at->isPast()) {
            $pending->delete();
            throw new \Exception('Verification token expired.');
        }

        $user = User::create([
            'name'     => $pending->name,
            'email'    => $pending->email,
            'password' => $pending->password,
        ]);

        $pending->delete();

        return $user;
    }

}
