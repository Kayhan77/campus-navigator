<?php

namespace App\Services\Auth;

use App\Models\PendingRegistration;
use App\Models\User;
use App\DTOs\Auth\RegisterPendingDTO;
use App\DTOs\Auth\VerifyCodeDTO;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Notifications\VerifyEmailNotification;

class PreRegisterService
{
    public function preRegister(RegisterPendingDTO $dto): PendingRegistration
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $pending = PendingRegistration::create([
            'name'              => $dto->name,
            'email'             => $dto->email,
            'password'          => Hash::make($dto->password),
            'token'             => Str::random(64),
            'verification_code' => $code,
            'expires_at'        => now()->addHours(24),
        ]);

        $pending->notify(new VerifyEmailNotification($code));

        return $pending;
    }

    public function verify(VerifyCodeDTO $dto): User
    {
        $pending = PendingRegistration::where('email', $dto->email)
            ->where('verification_code', $dto->code)
            ->first();

        if (!$pending) {
            throw new \InvalidArgumentException('Invalid verification code.');
        }

        if ($pending->expires_at->isPast()) {
            $pending->delete();
            throw new \InvalidArgumentException('Verification code has expired.');
        }

        $user = User::create([
            'name'     => $pending->name,
            'email'    => $pending->email,
            'password' => $pending->password,
            'is_verified' => true,
            'email_verified_at' => now(),
            'verification_code' => $pending->verification_code,
            'remember_token' => Str::random(60),
        ]);

        $pending->delete();

        return $user;
    }
}
