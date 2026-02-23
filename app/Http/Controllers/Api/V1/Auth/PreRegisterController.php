<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\DTOs\Auth\RegisterPendingDTO;
use App\DTOs\Auth\VerifyCodeDTO;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PreRegisterRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyCodeRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\Auth\PreRegisterService;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class PreRegisterController extends Controller
{
    public function __construct(
        private PreRegisterService $service
    ) {}

    public function register(PreRegisterRequest $request)
    {
        $dto     = RegisterPendingDTO::fromArray($request->validated());
        $pending = $this->service->preRegister($dto);

        return ApiResponse::success(
            ['email' => $pending->email, 'name' => $pending->name],
            'Verification code sent to your email.'
        );
    }

    public function verify(VerifyCodeRequest $request)
    {
        $dto  = VerifyCodeDTO::fromArray($request->validated());
        $user = $this->service->verify($dto);

        $token = JWTAuth::fromUser($user);

        return ApiResponse::success(
            [
                'user'         => new UserResource($user),
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'expires_in'   => JWTAuth::factory()->getTTL() * 60,
            ],
            'Email verified successfully.'
        );
    }

    public function resend(ResendOtpRequest $request)
    {
        $pending = $this->service->resendOtp($request->validated('email'));

        return ApiResponse::success(
            ['email' => $pending->email, 'name' => $pending->name],
            'Verification code resent successfully.'
        );
    }
}
