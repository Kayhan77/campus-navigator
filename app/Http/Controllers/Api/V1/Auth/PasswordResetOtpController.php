<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\DTOs\Auth\SendResetOtpDTO;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordOtpRequest;
use App\Services\Auth\PasswordResetOtpService;

class PasswordResetOtpController extends Controller
{
    public function __construct(
        private PasswordResetOtpService $service
    ) {}

    public function send(ForgotPasswordOtpRequest $request)
    {
        $dto = SendResetOtpDTO::fromArray($request->validated());

        $this->service->sendResetOtp($dto);

        return ApiResponse::success(
            null,
            'If that email is registered, a password reset code has been sent.'
        );
    }
}
