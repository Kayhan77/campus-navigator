<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeviceTokenRequest;
use App\Models\DeviceToken;

class DeviceTokenController extends Controller
{
    /**
     * Register or refresh a device token for the authenticated user.
     *
     * POST /api/v1/device-tokens
     */
    public function store(StoreDeviceTokenRequest $request)
    {
        $validated = $request->validated();

        // updateOrCreate prevents duplicate tokens and refreshes metadata
        $deviceToken = DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id'      => $request->user()->id,
                'platform'     => $validated['platform'] ?? null,
                'last_used_at' => now(),
            ]
        );

        $statusCode = $deviceToken->wasRecentlyCreated ? 201 : 200;
        $message    = $deviceToken->wasRecentlyCreated
            ? 'Device token registered successfully.'
            : 'Device token updated successfully.';

        return ApiResponse::success(
            [
                'id'           => $deviceToken->id,
                'platform'     => $deviceToken->platform,
                'last_used_at' => $deviceToken->last_used_at,
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
    public function destroy(StoreDeviceTokenRequest $request)
    {
        DeviceToken::where('token', $request->validated('token'))
            ->where('user_id', $request->user()->id)
            ->delete();

        return ApiResponse::success(null, 'Device token removed successfully.');
    }
}
