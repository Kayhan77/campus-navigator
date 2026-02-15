<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\JwtAuthController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\Event\EventController;
use App\Http\Controllers\Api\V1\LostFoundController;    
use App\Http\Controllers\Api\V1\AcademicScheduleController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\Auth\RefreshTokenController;
use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\RoomSearchController;

Route::prefix('v1')->group(function () {
  
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::post('/login', [JwtAuthController::class, 'login']);

});

Route::middleware('auth:api')->prefix('v1')->group(function () {

    Route::get('/me', [JwtAuthController::class, 'me']);
    Route::get('/user', fn ($request) => $request->user());

    Route::post('/logout', [JwtAuthController::class, 'logout']);
    Route::post('/refresh', [RefreshTokenController::class, 'refresh']);

    Route::get('/buildings', [BuildingController::class, 'index']);
    Route::get('/buildings/{id}', [BuildingController::class, 'show']);
    Route::post('/buildings', [BuildingController::class, 'store']);
    
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/{id}', [RoomController::class, 'show']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/search', [RoomSearchController::class, 'index']);

    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);

    Route::get('/lost-found', [LostFoundController::class, 'index']);
    Route::post('/lost-found', [LostFoundController::class, 'store']);

    Route::get('/schedule', [AcademicScheduleController::class, 'index']);
});
