<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Auth\AuthService;

class RefreshTokenController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Refresh the JWT token.
     */
    public function refresh(Request $request)
    {
        $token = $this->authService->refresh();

        return ApiResponse::success(['token' => $token], 'Token refreshed successfully');
    }
}
