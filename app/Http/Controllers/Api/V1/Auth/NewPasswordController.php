<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\DTOs\Auth\ResetPasswordDTO;
use App\Services\Auth\PasswordService;
use Illuminate\Http\JsonResponse;

class NewPasswordController extends Controller
{
    public function __construct(
        protected PasswordService $passwordService
    ) {}

    public function store(ResetPasswordRequest $request): JsonResponse
    {
        $dto = new ResetPasswordDTO($request->validated());

        $this->passwordService->resetPassword($dto);

        return response()->json([
            'message' => 'Password reset successfully.'
        ], 200);
    }
}
