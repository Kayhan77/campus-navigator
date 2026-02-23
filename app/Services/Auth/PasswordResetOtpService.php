<?php

namespace App\Services\Auth;

use App\DTOs\Auth\SendResetOtpDTO;
use App\DTOs\Auth\VerifyResetOtpDTO;
use App\Exceptions\ApiException;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetOtpService
{
    private const OTP_EXPIRY_MINUTES = 10;
    private const RESEND_COOLDOWN_SECONDS = 30;
    private const MAX_ATTEMPTS = 5;

    public function sendResetOtp(SendResetOtpDTO $dto): void
    {
        $user = User::where('email', $dto->email)->first();

        // Prevent email enumeration — always return same response
        if (! $user) {
            return;
        }

        $existing = PasswordResetOtp::where('email', $dto->email)->first();

        if ($existing && $existing->isOnCooldown()) {
            throw new ApiException(
                'Please wait before requesting another OTP.',
                429,
                ['seconds_remaining' => $existing->cooldownRemaining()]
            );
        }

        $otp = $this->generateOtp();

        PasswordResetOtp::updateOrCreate(
            ['email' => $dto->email],
            [
                'otp_hash'     => Hash::make($otp),
                'expires_at'   => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
                'attempts'     => 0,
                'last_sent_at' => now(),
            ]
        );

        Mail::to($dto->email)->queue(
            new PasswordResetOtpMail($otp, $user->name)
        );
    }

    public function verifyOtpAndResetPassword(VerifyResetOtpDTO $dto): void
    {
        $record = PasswordResetOtp::where('email', $dto->email)->first();

        if (! $record) {
            throw new ApiException('Invalid or expired OTP.', 422);
        }

        if ($record->isExpired()) {
            $record->delete();
            throw new ApiException('OTP has expired. Please request a new one.', 422);
        }

        if ($record->isMaxAttemptsReached()) {
            $record->delete();
            throw new ApiException(
                'Maximum verification attempts reached. Please request a new OTP.',
                429
            );
        }

        $record->increment('attempts');

        if (! Hash::check($dto->otp, $record->otp_hash)) {
            $attemptsLeft = self::MAX_ATTEMPTS - $record->attempts;

            throw new ApiException(
                'Invalid OTP.',
                422,
                ['attempts_remaining' => max(0, $attemptsLeft)]
            );
        }

        $user = User::where('email', $dto->email)->first();

        if (! $user) {
            throw new ApiException('User not found.', 404);
        }

        $user->forceFill([
            'password' => Hash::make($dto->password),
        ])->save();

        $record->delete();
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
