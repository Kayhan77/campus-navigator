<?php

namespace App\Services\Auth;

use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\ApiException;
use App\DTOs\Auth\RegisterData;

class AuthService
{
    public function register(RegisterData $data): array
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'role' => $data->role,
            ]);

            $token = JWTAuth::fromUser($user);

            return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
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

        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
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
    public function me()
    {
        return auth('api')->user();
    }
}
