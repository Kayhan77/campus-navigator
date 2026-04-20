<?php

use App\Http\Controllers\Api\V1\AcademicScheduleController;
use App\Http\Controllers\Api\V1\Admin\AdminAcademicScheduleController;
use App\Http\Controllers\Api\V1\Admin\AdminBuildingController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminEventController;
use App\Http\Controllers\Api\V1\Admin\AdminNewsController;
use App\Http\Controllers\Api\V1\Admin\AdminAnnouncementController;
use App\Http\Controllers\Api\V1\Admin\AdminRoomController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\AnnouncementController;
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
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\LostFoundController;
use App\Http\Controllers\Api\V1\ItemClaimController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\GlobalSearchController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Http\Controllers\Api\V1\Auth\GoogleController;
use Illuminate\Http\Request;


Route::get('/test-connection', function () {
    try {
        $fp = fsockopen('smtp.gmail.com', 587, $errno, $errstr, 10);

        if (!$fp) {
            return "Connection failed: $errstr ($errno)";
        }

        return "Connected successfully!";
    } catch (\Exception $e) {
        return $e->getMessage();
    }
});
Route::get('/test-mail', function () {
    Mail::raw('Test email', function ($msg) {
        $msg->to('forreplit121@email.com')
            ->subject('Test');
    });

    return 'Mail sent';
});

// Debug: Upload image to Supabase Storage and return test URLs
Route::post('/test-supabase/upload-image', function (Request $request) {
    $request->validate([
        'image' => ['required', 'image', 'max:5120'],
    ]);

    $supabaseUrl = rtrim((string) env('SUPABASE_URL'), '/');
    $serviceKey = (string) env('SUPABASE_SERVICE_ROLE_KEY');
    $bucket = (string) ($request->input('bucket') ?: env('SUPABASE_STORAGE_BUCKET', 'test-images'));

    if ($supabaseUrl === '' || $serviceKey === '') {
        return response()->json([
            'message' => 'Supabase credentials are missing. Set SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY.',
        ], 500);
    }

    $file = $request->file('image');
    $extension = $file->getClientOriginalExtension() ?: 'jpg';
    $path = 'tests/' . now()->format('Ymd_His') . '_' . Str::random(8) . '.' . strtolower($extension);
    $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));

    $httpClient = Http::withHeaders([
        'Authorization' => 'Bearer ' . $serviceKey,
        'apikey' => $serviceKey,
    ]);

    if (app()->environment('local')) {
        $httpClient = $httpClient->withoutVerifying();
    }

    $uploadClient = $httpClient->withHeaders([
        'x-upsert' => 'true',
        'Content-Type' => $file->getMimeType() ?: 'application/octet-stream',
    ]);

    /** @var \Illuminate\Http\Client\Response $uploadResponse */
    $uploadResponse = $uploadClient->withBody(
        file_get_contents($file->getRealPath()),
        $file->getMimeType() ?: 'application/octet-stream'
    )->post("{$supabaseUrl}/storage/v1/object/{$bucket}/{$encodedPath}");

    if ($uploadResponse->status() === 404) {
        $errorMessage = strtolower((string) data_get($uploadResponse->json(), 'message', ''));

        if (str_contains($errorMessage, 'bucket not found')) {
            // Debug helper: create the missing bucket automatically and retry once.
            /** @var \Illuminate\Http\Client\Response $createBucketResponse */
            $createBucketResponse = $httpClient->post("{$supabaseUrl}/storage/v1/bucket", [
                'id' => $bucket,
                'name' => $bucket,
                'public' => true,
            ]);

            if ($createBucketResponse->successful() || $createBucketResponse->status() === 409) {
                $uploadResponse = $uploadClient->withBody(
                    file_get_contents($file->getRealPath()),
                    $file->getMimeType() ?: 'application/octet-stream'
                )->post("{$supabaseUrl}/storage/v1/object/{$bucket}/{$encodedPath}");
            }
        }
    }

    if (! $uploadResponse->successful()) {
        return response()->json([
            'message' => 'Supabase upload failed.',
            'bucket' => $bucket,
            'details' => $uploadResponse->json() ?: $uploadResponse->body(),
            'hint' => 'Create this bucket in Supabase Storage or pass a different bucket name in request field "bucket".',
        ], $uploadResponse->status() ?: 500);
    }

    return response()->json([
        'message' => 'Image uploaded to Supabase successfully.',
        'bucket' => $bucket,
        'path' => $path,
        'public_url' => "{$supabaseUrl}/storage/v1/object/public/{$bucket}/{$encodedPath}",
        'test_get_url' => url('/api/test-supabase/image/' . $encodedPath) . '?bucket=' . rawurlencode($bucket),
    ]);
});

// Debug: Fetch image bytes from Supabase Storage through backend
Route::get('/test-supabase/image/{path}', function (Request $request, string $path) {
    $supabaseUrl = rtrim((string) env('SUPABASE_URL'), '/');
    $serviceKey = (string) env('SUPABASE_SERVICE_ROLE_KEY');
    $bucket = (string) ($request->query('bucket') ?: env('SUPABASE_STORAGE_BUCKET', 'test-images'));

    if ($supabaseUrl === '' || $serviceKey === '') {
        return response()->json([
            'message' => 'Supabase credentials are missing. Set SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY.',
        ], 500);
    }

    $httpClient = Http::withHeaders([
        'Authorization' => 'Bearer ' . $serviceKey,
        'apikey' => $serviceKey,
    ]);

    if (app()->environment('local')) {
        $httpClient = $httpClient->withoutVerifying();
    }

    /** @var \Illuminate\Http\Client\Response $getResponse */
    $getResponse = $httpClient->get("{$supabaseUrl}/storage/v1/object/{$bucket}/{$path}");

    if (! $getResponse->successful()) {
        return response()->json([
            'message' => 'Supabase fetch failed.',
            'bucket' => $bucket,
            'details' => $getResponse->json() ?: $getResponse->body(),
        ], $getResponse->status() ?: 500);
    }

    return response($getResponse->body(), 200, [
        'Content-Type' => $getResponse->header('Content-Type', 'application/octet-stream'),
        'Cache-Control' => 'no-store',
    ]);
})->where('path', '.*');

// Health check endpoint for Render
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'Campus Navigator API'
    ]);
});

// Debug: Clean up pending registrations and verification OTPs
Route::post('/debug/clean-registrations', function () {
    try {
        $cleanedTables = [];

        // Truncate pending_registrations
        $pendingCount = \App\Models\PendingRegistration::count();
        \App\Models\PendingRegistration::truncate();
        $cleanedTables['pending_registrations'] = $pendingCount;

        // Truncate email_verification_otps
        $otpCount = \App\Models\EmailVerificationOtp::count();
        \App\Models\EmailVerificationOtp::truncate();
        $cleanedTables['email_verification_otps'] = $otpCount;

        return response()->json([
            'message' => 'Database cleaned successfully',
            'cleaned_tables' => $cleanedTables
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});

// --- Public routes ---
Route::prefix('v1')->group(function () {

    Route::get('/auth/google', [GoogleController::class, 'redirect']);
    Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

    
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

    Route::get('/news',              [NewsController::class, 'index']);
    Route::get('/news/{news}',       [NewsController::class, 'show']);

    Route::get('/announcements',         [AnnouncementController::class, 'index']);
    Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show']);

    Route::get('/schedule',              [AcademicScheduleController::class, 'index']);
    Route::get('/schedule/{academicSchedule}', [AcademicScheduleController::class, 'show']);

    // Global cross-model search
    Route::get('/search', GlobalSearchController::class);

});
Route::middleware('auth:api')->get('/test-fcm', function (Request $request) {
    $user = $request->user();

    app(\App\Services\FirebaseService::class)->sendToUser(
        $user,
        'Test Title',
        'Hello from backend',
        ['type' => 'test']
    );

    return 'sent';
});

// --- Authenticated routes ---
Route::middleware('auth:api')->prefix('v1')->group(function () {
    

    Route::get('/me',       [JwtAuthController::class, 'me']);
    Route::post('/logout',  [JwtAuthController::class, 'logout']);
    Route::post('/refresh', [RefreshTokenController::class, 'refresh']);

    // Event registration
    Route::post('/events/{event}/register', [EventController::class, 'register']);
    Route::delete('/events/{event}/register', [EventController::class, 'unregister']);

    
    Route::get('/lost-found',  [LostFoundController::class, 'index']);
    Route::post('/lost-found', [LostFoundController::class, 'store']);

    // Item claim system for lost-and-found
    Route::post('/item-claims', [ItemClaimController::class, 'store']);
    Route::get('/lost-found/{lostItem}/claims', [ItemClaimController::class, 'index']);
    Route::patch('/item-claims/{claim}/accept', [ItemClaimController::class, 'accept']);
    Route::patch('/item-claims/{claim}/reject', [ItemClaimController::class, 'reject']);

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
    Route::get('/schedule/{academicSchedule}',       [AdminAcademicScheduleController::class, 'show']);
    Route::post('/schedule',                       [AdminAcademicScheduleController::class, 'store']);
    Route::put('/schedule/{academicSchedule}',     [AdminAcademicScheduleController::class, 'update']);
    Route::delete('/schedule/{academicSchedule}',  [AdminAcademicScheduleController::class, 'destroy']);

    // Announcement management
    Route::apiResource('announcements', AdminAnnouncementController::class);
    
    // News management
    Route::apiResource('news', AdminNewsController::class);

});

