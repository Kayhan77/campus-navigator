<?php

namespace App\Services\Auth;

use App\Models\PendingRegistration;
use App\Models\User;
use App\DTOs\Auth\RegisterPendingDTO;
use App\DTOs\Auth\VerifyCodeDTO;
use App\Exceptions\ApiException;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PreRegisterService
{
    public function preRegister(RegisterPendingDTO $dto): PendingRegistration
    {
        // Remove any stale pending registration for this email
        PendingRegistration::where('email', $dto->email)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $pending = PendingRegistration::create([
            'name'              => $dto->name,
            'email'             => $dto->email,
            'password'          => Hash::make($dto->password),
            'token'             => Str::random(64),
            'verification_code' => $code,
            'expires_at'        => now()->addMinutes(15),
        ]);

        $pending->notify(new VerifyEmailNotification($code));

        return $pending;
    }

    public function verify(VerifyCodeDTO $dto): User
    {
        $pending = PendingRegistration::where('email', $dto->email)->first();

        if (!$pending) {
            throw new \InvalidArgumentException('No pending registration found for this email.');
        }

        if ($pending->verification_code !== $dto->code) {
            throw new \InvalidArgumentException('Invalid verification code.');
        }

        if ($pending->expires_at->isPast()) {
            $pending->delete();
            throw new \InvalidArgumentException('Verification code has expired. Please register again.');
        }

        $user = User::create([
            'name'              => $pending->name,
            'email'             => $pending->email,
            'password'          => $pending->password,
            'is_verified'       => true,
            'email_verified_at' => now(),
        ]);

        $pending->delete();

        return $user;
    }

    public function resendOtp(string $email): PendingRegistration
    {
        $pending = PendingRegistration::where('email', $email)->first();

        if (!$pending) {
            throw new ApiException('No pending registration found for this email.', 404);
        }

        if ($pending->last_sent_at && $pending->last_sent_at->diffInSeconds(now()) < 30) {
            $remaining = 30 - $pending->last_sent_at->diffInSeconds(now());
            throw new ApiException("Please wait {$remaining} seconds before requesting a new code.", 429);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $pending->update([
            'verification_code' => $code,
            'expires_at'        => now()->addMinutes(15),
            'last_sent_at'      => now(),
        ]);

        $pending->notify(new VerifyEmailNotification($code));

        return $pending->fresh();
    }
}
