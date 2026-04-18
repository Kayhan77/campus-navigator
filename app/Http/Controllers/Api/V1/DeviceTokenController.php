<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeviceTokenRequest;
use App\Services\DeviceTokenService;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function __construct(
        private readonly DeviceTokenService $service
    ) {}

    /**
     * Register or refresh a device token for the authenticated user.
     *
     * POST /api/v1/device-tokens
     */
    public function store(StoreDeviceTokenRequest $request)
    {
        $deviceToken = $this->service->saveToken($request->user()->id, $request->validated());

        $statusCode = $deviceToken->wasRecentlyCreated ? 201 : 200;
        $message    = $deviceToken->wasRecentlyCreated
            ? 'Device token registered successfully.'
            : 'Device token updated successfully.';

        return ApiResponse::success(
            [
                'id'           => $deviceToken->id,
                'platform'     => $deviceToken->platform,
                'last_used_at' => $deviceToken->last_used_at,
                // Token is intentionally omitted from responses:
                // it is a secret credential and the client already holds it.
            ],
            $message,
            $statusCode
        );
    }

    /**
     * Remove a device token (e.g. on user logout from device).
     *
     * DELETE /api/v1/device-tokens
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string', 'min:20', 'max:512'],
        ]);

        $this->service->removeToken($request->user()->id, $request->token);

        return ApiResponse::success(null, 'Device token removed successfully.');
    }
}
