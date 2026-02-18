<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\PreRegisterController;


Route::get('/verify-email', [PreRegisterController::class, 'verifyFromEmail'])
    ->name('verification.email');

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

require __DIR__.'/auth.php';
