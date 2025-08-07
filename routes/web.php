<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::get('/tentang-kami', function () {
    return Inertia::render('About');
})->name('about-us');

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');
