<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Auth\AuthService;
use App\Helpers\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Check;

class JwtAuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Log in with JWT.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $token = $this->authService->login($request->email, $request->password);

        return ApiResponse::success(['token' => $token], 'Login successful');
    }

    /**
     * Refresh JWT token.
     */
    public function refresh()
    {
        $token = $this->authService->refresh();
        return ApiResponse::success(['token' => $token], 'Token refreshed successfully');
    }

    /**
     * Logout user (invalidate JWT).
     */
    public function logout()
    {
        $this->authService->logout();
        return ApiResponse::success(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user info.
     */
    public function me()
    {
        $user = $this->authService->me();
        return ApiResponse::success(['user' => $user]);
    }
}
