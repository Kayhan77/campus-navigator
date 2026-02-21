<?php

namespace App\Services\Auth;

use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\ApiException;
use App\DTOs\Auth\RegisterData;
use App\DTOs\Auth\VerifyCodeDTO;

class AuthService
{
    /**
     * Register a new user with verification code (unverified).
     */
    public function register(RegisterData $data): array
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'name'              => $data->name,
            'email'             => $data->email,
            'password'          => Hash::make($data->password),
            'role'              => $data->role,
            'verification_code' => $code,
            'is_verified'       => false,
        ]);

        return [
            'user' => $user,
            'code' => $code,
        ];
    }

    /**
     * Verify a user's email using the 6-digit verification code.
     */
    public function verifyCode(VerifyCodeDTO $dto): User
    {
        $user = User::where('email', $dto->email)
            ->where('verification_code', $dto->code)
            ->first();

        if (!$user) {
            throw new ApiException('Invalid verification code.', 422);
        }

        $user->update([
            'is_verified'       => true,
            'email_verified_at' => now(),
            'verification_code' => null,
        ]);

        return $user->fresh();
    }

    /**
     * Login user and return JWT token (only if verified).
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new ApiException('Invalid credentials', 401);
        }

        if (!$user->is_verified) {
            throw new ApiException('Please verify your email before logging in.', 403);
        }

        $token = $this->createToken($user);

        return [
            'user' => $user,
            ...$token,
        ];
    }

    /**
     * Refresh the JWT token.
     */
    public function refresh(?string $token = null): array
    {
        try {
            $token = $token ?? JWTAuth::getToken();
            $newToken = JWTAuth::refresh($token);

            return [
                'access_token' => $newToken,
                'token_type'   => 'Bearer',
                'expires_in'   => JWTAuth::factory()->getTTL() * 60,
            ];
        } catch (JWTException $e) {
            throw new ApiException('Cannot refresh token', 401);
        }
    }

    /**
     * Logout user (invalidate token).
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
     * Get authenticated user.
     */
    public function me(): ?User
    {
        return auth('api')->user();
    }

    /**
     * Create JWT token for a given user.
     */
    protected function createToken(User $user): array
    {
        $token = JWTAuth::fromUser($user);

        return [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60,
        ];
    }
}
