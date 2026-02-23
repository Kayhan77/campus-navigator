<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\DTOs\Auth\RegisterPendingDTO;
use App\DTOs\Auth\VerifyCodeDTO;
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

        return response()->json([
            'message' => 'Verification code sent to your email.',
            'data'    => [
                'email' => $pending->email,
                'name'  => $pending->name,
            ],
        ]);
    }

    public function verify(VerifyCodeRequest $request)
    {
        try {
            $dto  = VerifyCodeDTO::fromArray($request->validated());
            $user = $this->service->verify($dto);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'message'      => 'Email verified successfully.',
                'user'         => new UserResource($user),
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'expires_in'   => JWTAuth::factory()->getTTL() * 60,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function resend(ResendOtpRequest $request)
    {
        try {
            $pending = $this->service->resendOtp($request->validated('email'));

            return response()->json([
                'message' => 'Verification code resent successfully.',
                'data'    => [
                    'email' => $pending->email,
                    'name'  => $pending->name,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
