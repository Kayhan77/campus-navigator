<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Manages per-user push notification preferences.
 *
 * GET    /api/v1/notification-preferences      → show current prefs
 * PATCH  /api/v1/notification-preferences      → update prefs (partial)
 * DELETE /api/v1/notification-preferences      → reset to defaults
 */
class NotificationPreferencesController extends Controller
{
    private const VALID_WINDOWS = ['24h', '1h', '10min'];

    /**
     * Return the authenticated user's notification preferences.
     * Falls back to the default shape if none have been saved yet.
     */
    public function show(Request $request)
    {
        return ApiResponse::success(
            $request->user()->notification_preferences,
            'Notification preferences retrieved.'
        );
    }

    /**
     * Partially update notification preferences.
     *
     * Accepted fields (all optional):
     *  - enabled   (bool)
     *  - reminders (array of "24h"|"1h"|"10min")
     *  - locale    (string, BCP-47, max 10)
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled'   => ['sometimes', 'boolean'],
            'reminders' => ['sometimes', 'array'],
            'reminders.*' => ['string', 'in:' . implode(',', self::VALID_WINDOWS)],
            'locale'    => ['sometimes', 'string', 'max:10'],
        ]);

        $user          = $request->user();
        $current       = $user->notification_preferences;   // already merged with defaults
        $updated       = array_merge($current, $validated);

        // Ensure reminders are unique and valid
        if (isset($updated['reminders'])) {
            $updated['reminders'] = array_values(
                array_unique(
                    array_filter($updated['reminders'], fn ($r) => in_array($r, self::VALID_WINDOWS, true))
                )
            );
        }

        $user->update(['notification_preferences' => $updated]);

        return ApiResponse::success($updated, 'Notification preferences updated.');
    }

    /**
     * Reset preferences to platform defaults.
     */
    public function destroy(Request $request)
    {
        $request->user()->update(['notification_preferences' => null]);

        return ApiResponse::success(
            $request->user()->notification_preferences,  // returns the defaults
            'Notification preferences reset to defaults.'
        );
    }
}
