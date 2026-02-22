<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PreRegisterRequest;
use App\Http\Requests\Auth\VerifyCodeRequest;
use App\Http\Resources\Auth\PendingRegistrationResource;
use App\Http\Resources\Auth\UserResource;
use App\Services\Auth\PreRegisterService;
use App\DTOs\Auth\RegisterPendingDTO;
use App\DTOs\Auth\VerifyCodeDTO;

class PreRegisterController extends Controller
{
    public function __construct(
        private PreRegisterService $service
    ) {}

    public function register(PreRegisterRequest $request)
    {
        $dto = RegisterPendingDTO::fromArray($request->validated());

        $pending = $this->service->preRegister($dto);

        return response()->json([
            'message' => 'Pre-registration successful. Check your email for the verification code.',
            'data' => new PendingRegistrationResource($pending),
        ]);
    }

    public function verify(VerifyCodeRequest $request)
    {
        try {
            $dto = VerifyCodeDTO::fromArray($request->validated());

            $user = $this->service->verify($dto);

            return response()->json([
                'message' => 'Email verified successfully.',
                'user'    => new UserResource($user),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
            
        }
    }
}
