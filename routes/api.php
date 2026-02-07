<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LostFoundController;
use App\Http\Controllers\AcademicScheduleController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn ($request) => $request->user());

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/buildings', [BuildingController::class, 'index']);
    Route::get('/buildings/{id}', [BuildingController::class, 'show']);

    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);

    Route::get('/lost-found', [LostFoundController::class, 'index']);
    Route::post('/lost-found', [LostFoundController::class, 'store']);

    Route::get('/schedule', [AcademicScheduleController::class, 'index']);
});
