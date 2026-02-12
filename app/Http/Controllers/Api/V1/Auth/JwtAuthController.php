<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Auth\AuthService;
use App\Helpers\ApiResponse;

class JwtAuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $data = $this->authService->login($request->email, $request->password);

        return ApiResponse::success($data, 'Login successful');
    }

    public function refresh()
    {
        
    }

    public function logout()
    {
        $this->authService->logout();
        return ApiResponse::success(null, 'Logged out successfully');
    }

    public function me()
    {
        $user = $this->authService->me();
        return ApiResponse::success(['user' => $user]);
    }
}
