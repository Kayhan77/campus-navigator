<?php

namespace App\Services\Auth;

use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\ApiException;
use App\DTOs\Auth\RegisterData;
use App\DTOs\Auth\ResetPasswordDTO;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Register a new user and return JWT token
     */
    public function register(RegisterData $data): array
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'role' => $data->role,
        ]);

        $token = $this->createToken($user);

        return [
            'user' => $user,
            ...$token,
        ];
    }

    /**
     * Login user and return JWT token
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new ApiException('Invalid credentials', 401);
        }

        $token = $this->createToken($user);

        return [
            'user' => $user,
            ...$token,
        ];
    }

    /**
     * Refresh the JWT token
     */
    public function refresh(?string $token = null): array
    {
        try {
            $token = $token ?? JWTAuth::getToken();
            $newToken = JWTAuth::refresh($token);

            return [
                'access_token' => $newToken,
                'token_type' => 'Bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ];
        } catch (JWTException $e) {
            throw new ApiException('Cannot refresh token', 401);
        }
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout(): void
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            throw new ApiException('Cannot logout user', 400);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(): ?User
    {
        return auth('api')->user();
    }

    /**
     * Create JWT token for a given user
     */
    protected function createToken(User $user): array
    {
        $token = JWTAuth::fromUser($user);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ];
    }

    // public function sendResetLink(string $email): void
    // {
    //     $status = Password::sendResetLink(['email' => $email]);

    //     if ($status !== Password::RESET_LINK_SENT) {
    //         throw new \Exception(__($status));
    //     }
    // }

    // public function resetPassword(ResetPasswordDTO $data): void
    // {
    //     $status = Password::reset(
    //         $data->toArray(),
    //         function ($user) use ($data) {
    //             $user->forceFill([
    //                 'password' => Hash::make($data->password),
    //                 'remember_token' => Str::random(60),
    //             ])->save();

    //             event(new PasswordReset($user));
    //         }
    //     );

    //     if ($status !== Password::PASSWORD_RESET) {
    //         throw new \Exception(__($status));
    //     }
    // }
}
