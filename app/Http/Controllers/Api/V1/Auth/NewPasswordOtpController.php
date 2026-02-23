<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\DTOs\Auth\VerifyResetOtpDTO;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyResetOtpRequest;
use App\Services\Auth\PasswordResetOtpService;

class NewPasswordOtpController extends Controller
{
    public function __construct(
        private PasswordResetOtpService $service
    ) {}

    public function reset(VerifyResetOtpRequest $request)
    {
        $dto = VerifyResetOtpDTO::fromArray($request->validated());

        $this->service->verifyOtpAndResetPassword($dto);

        return ApiResponse::success(
            null,
            'Password has been reset successfully. You can now log in with your new password.'
        );
    }
}
