<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\GoogleController;

Route::get('/', function () {
    return 'OK';
});

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});
Route::middleware(['web'])->group(function () {
    Route::get('/auth/google', [GoogleController::class, 'redirect']);
    Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
});

require __DIR__.'/auth.php';
