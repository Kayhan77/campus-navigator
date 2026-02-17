<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\DTOs\Auth\SendResetLinkDTO;
use App\Services\Auth\PasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;

class PasswordResetLinkController extends Controller
{
    public function __construct(
        protected PasswordService $passwordService
    ) {}

    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        $dto = new SendResetLinkDTO($request->validated());

        $this->passwordService->sendResetLink($dto);

        return response()->json([
            'message' => 'Password reset link sent successfully.'
        ], 200);

        $user->notify(new ResetPasswordNotification($link));

    }
}
