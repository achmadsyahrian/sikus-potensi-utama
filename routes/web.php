<?php

use App\Http\Controllers\AcademicPeriodController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramStudyController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/about-us', function () {
        return Inertia::render('About');
    })->name('about-us');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('/users', UserController::class)->names('users');
    Route::post('/reset-password/{user}', [UserController::class, 'resetPassword'])->name('users.reset-password');


    // =========== Data Master ===========
    Route::get('/academic-periods', [AcademicPeriodController::class, 'index'])->name('academic-periods.index');
    Route::post('/academic-periods/sync', [AcademicPeriodController::class, 'sync'])->name('academic-periods.sync');

    Route::get('/faculties', [FacultyController::class, 'index'])->name('faculties.index');
    Route::post('/faculties/sync', [FacultyController::class, 'sync'])->name('faculties.sync');

    Route::get('/program-studies', [ProgramStudyController::class, 'index'])->name('program-studies.index');
    Route::post('/program-studies/sync', [ProgramStudyController::class, 'sync'])->name('program-studies.sync');
});


require __DIR__ . '/auth.php';
