<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Throwable;

class AuthService
{
    /**
     * Login user and return JWT token
     */
    public function login(string $email, string $password): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (! $user || ! Hash::check($password, $user->password)) {
                throw new ApiException('Invalid credentials', 401);
            }

            if (! $user->is_verified) {
                throw new ApiException('Email not verified', 403);
            }

            return [
                'user' => $user,
                ...$this->createToken($user),
            ];
        } catch (ApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            logger()->error('Auth service operation failed', [
                'operation' => 'login_failed',
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'email' => $email,
            ]);

            throw new ApiException('Unable to login at this time.', 500);
        }
    }

    /**
     * Refresh the JWT token
     */
    public function refresh(?string $token = null): array
    {
        try {
            $token    = $token ?? JWTAuth::getToken();
            $newToken = JWTAuth::refresh($token);

            return [
                'access_token' => $newToken,
                'token_type'   => 'Bearer',
                'expires_in'   => JWTAuth::factory()->getTTL() * 60,
            ];
        } catch (JWTException $e) {
            logger()->error('Auth service operation failed', [
                'operation' => 'refresh_token_failed',
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'user_id' => auth('api')->id(),
            ]);

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
            logger()->error('Auth service operation failed', [
                'operation' => 'logout_failed',
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'user_id' => auth('api')->id(),
            ]);

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
    public function createToken(User $user): array
    {
        $token = JWTAuth::fromUser($user);

        return [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60,
        ];
    }
}