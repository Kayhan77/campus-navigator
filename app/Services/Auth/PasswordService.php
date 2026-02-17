<?php

namespace App\Services\Auth;

use App\DTOs\Auth\ResetPasswordDTO;
use App\DTOs\Auth\SendResetLinkDTO;
use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordService
{
    /**
     * Send password reset link email
     */
    public function sendResetLink(SendResetLinkDTO $dto): void
    {
        $status = Password::sendResetLink(
            $dto->toArray()
        );

        if ($status !== Password::RESET_LINK_SENT) {
            throw new ApiException(__($status), 400);
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(ResetPasswordDTO $dto): void
    {
        $status = Password::reset(
            $dto->toArray(),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new ApiException(__($status), 400);
        }
    }
}
