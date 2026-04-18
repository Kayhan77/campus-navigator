<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeviceTokenRequest;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

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

        DeviceToken::where('token', $request->token)
            ->where('user_id', $request->user()->id)
            ->delete();

        return ApiResponse::success(null, 'Device token removed successfully.');
    }

    /**
     * Save single FCM token directly on users table.
     *
     * POST /api/v1/save-fcm-token
     */
    public function saveFcmToken(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'min:20', 'max:512'],
        ]);

        $request->user()->update([
            'fcm_token' => $validated['fcm_token'],
        ]);

        return ApiResponse::success(null, 'FCM token saved successfully.');
    }
}
