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
use Throwable;

class PasswordService
{
    /**
     * Send password reset link email
     */
    public function sendResetLink(SendResetLinkDTO $dto): void
    {
        try {
            $status = Password::sendResetLink(
                $dto->toArray()
            );

            if ($status !== Password::RESET_LINK_SENT) {
                throw new ApiException(__($status), 400);
            }
        } catch (ApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            logger()->error('Password service operation failed', [
                'operation' => 'send_reset_link_failed',
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'email' => $dto->email,
            ]);

            throw new ApiException('Unable to send password reset link at this time.', 500);
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(ResetPasswordDTO $dto): void
    {
        try {
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
        } catch (ApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            logger()->error('Password service operation failed', [
                'operation' => 'reset_password_failed',
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'email' => $dto->email,
            ]);

            throw new ApiException('Unable to reset password at this time.', 500);
        }
    }
}
