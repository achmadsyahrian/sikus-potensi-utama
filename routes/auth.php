<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'index'])
        ->name('login');

    Route::post('login', [AuthController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'destroy'])
        ->name('logout');
});
