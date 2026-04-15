<?php

use App\Http\Controllers\Api\V1\AcademicScheduleController;
use App\Http\Controllers\Api\V1\Admin\AdminAcademicScheduleController;
use App\Http\Controllers\Api\V1\Admin\AdminBuildingController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminEventController;
use App\Http\Controllers\Api\V1\Admin\AdminRoomController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Auth\JwtAuthController;
use App\Http\Controllers\Api\V1\Auth\NewPasswordOtpController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetOtpController;
use App\Http\Controllers\Api\V1\Auth\PreRegisterController;
use App\Http\Controllers\Api\V1\Auth\RefreshTokenController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\NotificationPreferencesController;
use App\Http\Controllers\Api\V1\Event\EventController;
use App\Http\Controllers\Api\V1\Event\EventCalendarController;
use App\Http\Controllers\Api\V1\LostFoundController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\GlobalSearchController;
use App\Http\Controllers\Api\V1\RoomSearchController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;


Route::get('/test-mail', function () {
    Mail::raw('Test email', function ($msg) {
        $msg->to('your@email.com')
            ->subject('Test');
    });

    return 'Mail sent';
});

// Health check endpoint for Render
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'Campus Navigator API'
    ]);
});

// Debug: Clean up pending registrations and verification OTPs
Route::post('/debug/clean-registrations', function (Request $request) {
    // Security check: only allow in non-production or with valid debug key
    if (config('app.env') === 'production') {
        $debugKey = $request->query('key');
        $expectedKey = config('app.debug_key'); // Set this in .env as DEBUG_KEY=your-secret-key

        if (!$debugKey || $debugKey !== $expectedKey) {
            return response()->json([
                'error' => 'Unauthorized. Production environment requires valid debug key.',
                'status' => 'failed'
            ], 403);
        }
    }

    // Check if debug mode is enabled (optional double-check)
    if (!config('app.debug') && config('app.env') === 'production') {
        return response()->json([
            'error' => 'Debug operations not allowed in production with debug disabled.',
            'status' => 'failed'
        ], 403);
    }

    try {
        $cleanedTables = [];

        // Truncate pending_registrations
        $pendingCount = \App\Models\PendingRegistration::count();
        \App\Models\PendingRegistration::truncate();
        $cleanedTables['pending_registrations'] = [
            'records_deleted' => $pendingCount,
            'status' => 'cleaned'
        ];

        // Truncate email_verification_otps
        $otpCount = \App\Models\EmailVerificationOtp::count();
        \App\Models\EmailVerificationOtp::truncate();
        $cleanedTables['email_verification_otps'] = [
            'records_deleted' => $otpCount,
            'status' => 'cleaned'
        ];

        return response()->json([
            'message' => 'Database cleaned successfully',
            'status' => 'success',
            'cleaned_tables' => $cleanedTables,
            'timestamp' => now()->toIso8601String()
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to clean database',
            'message' => $e->getMessage(),
            'status' => 'failed'
        ], 500);
    }
})->name('debug.clean-registrations');

// --- Public routes ---
Route::prefix('v1')->group(function () {

    Route::post('/pre-register', [PreRegisterController::class, 'register']);
    Route::post('/verify-otp',   [PreRegisterController::class, 'verify']);
    Route::post('/resend-otp',   [PreRegisterController::class, 'resend'])->middleware('throttle:5,1');
    Route::post('/login',        [JwtAuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordResetOtpController::class, 'send'])
        ->middleware('throttle:5,1');
    Route::post('/reset-password', [NewPasswordOtpController::class, 'reset']);


    Route::get('/buildings',         [BuildingController::class, 'index']);
    Route::get('/buildings/{building}', [BuildingController::class, 'show']);

    Route::get('/rooms',             [RoomController::class, 'index']);
    Route::get('/rooms/{room}',      [RoomController::class, 'show']);

    Route::get('/events',            [EventController::class, 'index']);
    Route::get('/events/{event}',    [EventController::class, 'show']);
    Route::get('/calendar/events',   [EventCalendarController::class, 'index']);

    Route::get('/schedule',              [AcademicScheduleController::class, 'index']);
    Route::get('/schedule/{academicSchedule}', [AcademicScheduleController::class, 'show']);

    // Global cross-model search
    Route::get('/search', GlobalSearchController::class);

});

// --- Authenticated routes ---
Route::middleware('auth:api')->prefix('v1')->group(function () {

    Route::get('/me',       [JwtAuthController::class, 'me']);
    Route::post('/logout',  [JwtAuthController::class, 'logout']);
    Route::post('/refresh', [RefreshTokenController::class, 'refresh']);

    
    Route::get('/rooms/search', [RoomSearchController::class, 'index']);

    Route::get('/lost-found',  [LostFoundController::class, 'index']);
    Route::post('/lost-found', [LostFoundController::class, 'store']);

    // Device token registration for push notifications
    Route::post('/device-tokens', [DeviceTokenController::class, 'store'])
        ->middleware('throttle:10,1');
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy'])
        ->middleware('throttle:10,1');

    // Push notification preferences
    Route::get('/notification-preferences',    [NotificationPreferencesController::class, 'show']);
    Route::patch('/notification-preferences',  [NotificationPreferencesController::class, 'update']);
    Route::delete('/notification-preferences', [NotificationPreferencesController::class, 'destroy']);

});

// --- Admin routes ---
Route::middleware(['auth:api', 'admin'])->prefix('v1/admin')->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // User management
    Route::get('/users',               [AdminUserController::class, 'index']);
    Route::get('/users/{user}',        [AdminUserController::class, 'show']);
    Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole']);

    // Event management
    Route::get('/events',              [AdminEventController::class, 'index']);
    Route::get('/events/{event}',      [AdminEventController::class, 'show']);
    Route::post('/events',             [AdminEventController::class, 'store']);
    Route::put('/events/{event}',      [AdminEventController::class, 'update']);
    Route::delete('/events/{event}',   [AdminEventController::class, 'destroy']);

    // Building management
    Route::get('/buildings',               [AdminBuildingController::class, 'index']);
    Route::get('/buildings/{building}',    [AdminBuildingController::class, 'show']);
    Route::post('/buildings',              [AdminBuildingController::class, 'store']);
    Route::put('/buildings/{building}',    [AdminBuildingController::class, 'update']);
    Route::delete('/buildings/{building}', [AdminBuildingController::class, 'destroy']);

    // Room management
    Route::get('/rooms',             [AdminRoomController::class, 'index']);
    Route::get('/rooms/{room}',      [AdminRoomController::class, 'show']);
    Route::post('/rooms',            [AdminRoomController::class, 'store']);
    Route::put('/rooms/{room}',      [AdminRoomController::class, 'update']);
    Route::delete('/rooms/{room}',   [AdminRoomController::class, 'destroy']);

    // Academic schedule management
    Route::get('/schedule',                        [AdminAcademicScheduleController::class, 'index']);
    Route::get('/schedule/{academicSchedule}',     [AdminAcademicScheduleController::class, 'show']);
    Route::post('/schedule',                       [AdminAcademicScheduleController::class, 'store']);
    Route::put('/schedule/{academicSchedule}',     [AdminAcademicScheduleController::class, 'update']);
    Route::delete('/schedule/{academicSchedule}',  [AdminAcademicScheduleController::class, 'destroy']);

});

