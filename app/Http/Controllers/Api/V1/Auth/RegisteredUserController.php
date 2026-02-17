<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use App\DTOs\Auth\RegisterData;
use App\Helpers\ApiResponse;

class RegisteredUserController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user.
     */
    public function store(RegisterRequest $request)
    {
        $data = new RegisterData($request->validated());
        $user = $this->authService->register($data);

        return ApiResponse::success($user, 'User registered successfully', 201);
    }
}
