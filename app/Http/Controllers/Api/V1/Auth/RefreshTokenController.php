<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use App\Services\Auth\AuthService;

class RefreshTokenController extends Controller
{
    protected AuthService $authService;
    /**
     * Refresh the JWT token.
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    public function refresh(Request $request)
    {
        try {

            $data = $this->authService->refresh();
            return ApiResponse::success($data, 'Token refreshed successfully');

        } catch (TokenInvalidException $e) {
            return ApiResponse::error('Invalid token', 401);
        } catch (JWTException $e) {
            return ApiResponse::error('Token not provided', 400);
        }
    }
}
