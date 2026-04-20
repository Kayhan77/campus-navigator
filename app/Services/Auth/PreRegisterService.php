<?php

namespace App\Services\Auth;

use App\DTOs\Auth\RegisterPendingDTO;
use App\DTOs\Auth\VerifyCodeDTO;
use App\Exceptions\ApiException;
use App\Models\EmailVerificationOtp;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class PreRegisterService
{
    private const OTP_EXPIRY_MINUTES      = 10;
    private const RESEND_COOLDOWN_SECONDS = 30;
    private const MAX_ATTEMPTS            = 5;

    public function preRegister(RegisterPendingDTO $dto): PendingRegistration
    {
        try {
            // Remove any stale records for this email
            PendingRegistration::where('email', $dto->email)->delete();
            EmailVerificationOtp::where('email', $dto->email)->delete();

            $pending = PendingRegistration::create([
                'name'     => $dto->name,
                'email'    => $dto->email,
                'password' => Hash::make($dto->password),
                'token'    => Str::random(64),
            ]);

            $otp = $this->generateOtp();

            EmailVerificationOtp::create([
                'email'        => $dto->email,
                'otp_hash'     => Hash::make($otp),
                'expires_at'   => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
                'attempts'     => 0,
                'last_sent_at' => now(),
            ]);

            $pending->notify(new VerifyEmailNotification($otp));

            return $pending;
        } catch (ApiException|ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logServiceError('pre_register_failed', $e, [
                'email' => $dto->email,
            ]);

            throw new ApiException('Unable to complete registration at this time.', 500);
        }
    }

    public function verify(VerifyCodeDTO $dto): User
    {
        try {
            $pending = PendingRegistration::where('email', $dto->email)->first();

            if (! $pending) {
                throw new ApiException('No pending registration found for this email.', 422);
            }

            $otpRecord = EmailVerificationOtp::where('email', $dto->email)->first();

            if (! $otpRecord) {
                throw new ApiException('No verification code found. Please request a new one.', 422);
            }

            if ($otpRecord->isExpired()) {
                $otpRecord->delete();
                throw new ApiException('Verification code has expired. Please request a new one.', 422);
            }

            if ($otpRecord->isMaxAttemptsReached()) {
                $otpRecord->delete();
                $pending->delete();
                throw new ApiException(
                    'Maximum verification attempts reached. Please register again.',
                    429
                );
            }

            $otpRecord->increment('attempts');

            if (! Hash::check($dto->code, $otpRecord->otp_hash)) {
                $attemptsLeft = self::MAX_ATTEMPTS - $otpRecord->attempts;

                throw new ApiException(
                    'Invalid verification code.',
                    422,
                    ['attempts_remaining' => max(0, $attemptsLeft)]
                );
            }

            $user = User::create([
                'name'              => $pending->name,
                'email'             => $pending->email,
                'password'          => $pending->password,
                'is_verified'       => true,
                'email_verified_at' => now(),
            ]);

            $otpRecord->delete();
            $pending->delete();

            return $user;
        } catch (ApiException|ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logServiceError('verify_registration_code_failed', $e, [
                'email' => $dto->email,
            ]);

            throw new ApiException('Unable to verify code at this time.', 500);
        }
    }

    public function resendOtp(string $email): PendingRegistration
    {
        try {
            $pending = PendingRegistration::where('email', $email)->first();

            if (! $pending) {
                throw new ApiException('No pending registration found for this email.', 404);
            }

            $otpRecord = EmailVerificationOtp::where('email', $email)->first();

            if ($otpRecord && $otpRecord->isOnCooldown()) {
                throw new ApiException(
                    'Please wait before requesting another code.',
                    429,
                    ['seconds_remaining' => $otpRecord->cooldownRemaining()]
                );
            }

            $otp = $this->generateOtp();

            EmailVerificationOtp::updateOrCreate(
                ['email' => $email],
                [
                    'otp_hash'     => Hash::make($otp),
                    'expires_at'   => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
                    'attempts'     => 0,
                    'last_sent_at' => now(),
                ]
            );

            $pending->notify(new VerifyEmailNotification($otp));

            return $pending;
        } catch (ApiException|ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logServiceError('resend_registration_otp_failed', $e, [
                'email' => $email,
            ]);

            throw new ApiException('Unable to resend verification code at this time.', 500);
        }
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function logServiceError(string $operation, Throwable $e, array $context = []): void
    {
        logger()->error('Pre-register service operation failed', array_merge([
            'operation' => $operation,
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ], $context));
    }
}
