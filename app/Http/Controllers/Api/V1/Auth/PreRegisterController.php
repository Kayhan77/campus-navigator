<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PreRegisterRequest;
use App\Http\Resources\Auth\PendingRegistrationResource;
use App\Services\Auth\PreRegisterService;
use App\DTOs\Auth\RegisterPendingDTO;
use Illuminate\Http\Request;

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
            'message' => 'Pre-registration successful. Please verify your email.',
            'data' => new PendingRegistrationResource($pending),
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $user = $this->service->verify($request->token);

        return response()->json([
            'message' => 'Email verified successfully.',
            'user' => $user
        ]);
    }
}
