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
use App\Http\Controllers\Api\V1\Event\EventController;
use App\Http\Controllers\Api\V1\LostFoundController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\RoomSearchController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/schedule',              [AcademicScheduleController::class, 'index']);
    Route::get('/schedule/{academicSchedule}', [AcademicScheduleController::class, 'show']);

});

// --- Authenticated routes ---
Route::middleware('auth:api')->prefix('v1')->group(function () {

    Route::get('/me',       [JwtAuthController::class, 'me']);
    Route::post('/logout',  [JwtAuthController::class, 'logout']);
    Route::post('/refresh', [RefreshTokenController::class, 'refresh']);

    
    Route::get('/rooms/search', [RoomSearchController::class, 'index']);

    Route::get('/lost-found',  [LostFoundController::class, 'index']);
    Route::post('/lost-found', [LostFoundController::class, 'store']);

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
    Route::get('/events/{event}',       [AdminEventController::class, 'show']);
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

