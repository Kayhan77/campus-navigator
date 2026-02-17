<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Auth\AuthService;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

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
        try {
            $token = $this->authService->refresh();
            return ApiResponse::success(['token' => $token], 'Token refreshed successfully');
        } catch (TokenInvalidException $e) {
            return ApiResponse::error('Invalid token', 401);
        } catch (JWTException $e) {
            return ApiResponse::error('Token not provided', 400);
        }
    }
}
