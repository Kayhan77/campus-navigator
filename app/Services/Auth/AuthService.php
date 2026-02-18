<?php

namespace App\Services\Auth;

use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\ApiException;
use App\DTOs\Auth\RegisterData;
use App\Models\EmailVerification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\DTOs\Auth\RegisterPendingDTO;


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

    public function registerPending(RegisterPendingDTO $data): void
    {
        // Delete old token if exists
        EmailVerification::where('email', $data->email)->delete();

        $token = Str::random(64);
        $expires = now()->addMinutes(60);

        EmailVerification::create([
            'email' => $data->email,
            'token' => $token,
            'expires_at' => $expires,
        ]);

        // Send email
        $link = config('app.frontend_url') . "/verify-email?token={$token}&email={$data->email}";

        Mail::send([], [], function ($message) use ($data, $link) {
            $message->to($data->email)
                ->subject('Verify Your Email')
                ->setBody("Hello {$data->name}, click here to verify your email: {$link}", 'text/html');
        });
    }

    public function verifyEmail(string $token, string $email): User
    {
        $record = EmailVerification::where('email', $email)
            ->where('token', $token)
            ->where('expires_at', '>=', now())
            ->first();

        if (!$record) {
            throw new \Exception('Invalid or expired verification token');
        }

        // Create user
        $user = User::create([
            'name' => $record->email, // optionally store name elsewhere
            'email' => $record->email,
            'password' => Hash::make('defaultpassword'), // OR store pending password encrypted
        ]);

        $record->delete(); // remove token

        return $user;
    }
}
