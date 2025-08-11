<?php

use App\Http\Controllers\AcademicPeriodController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramStudyController;
use App\Http\Controllers\QuestionCategoryController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\QuestionOptionController;
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


    // =========== Questionnaire Management ===========
    Route::get('/questionnaires', [QuestionnaireController::class, 'index'])->name('questionnaires.index');
    Route::get('/questionnaires/create', [QuestionnaireController::class, 'create'])->name('questionnaires.create');
    Route::post('/questionnaires', [QuestionnaireController::class, 'store'])->name('questionnaires.store');
    Route::get('/questionnaires/{questionnaire}', [QuestionnaireController::class, 'show'])->name('questionnaires.show');
    Route::put('/questionnaires/{questionnaire}', [QuestionnaireController::class, 'update'])->name('questionnaires.update');
    Route::delete('/questionnaires/{questionnaire}', [QuestionnaireController::class, 'destroy'])->name('questionnaires.destroy');

    // =========== Questionnaire Category ===========
    Route::post('/question-categories', [QuestionCategoryController::class, 'store'])->name('question-categories.store');
    Route::put('/question-categories/{category}', [QuestionCategoryController::class, 'update'])->name('question-categories.update');
    Route::delete('/question-categories/{category}', [QuestionCategoryController::class, 'destroy'])->name('question-categories.destroy');

    // =========== Questionnaire Option ===========
    Route::post('/question-options', [QuestionOptionController::class, 'store'])->name('question-options.store');
    Route::put('/question-options/{option}', [QuestionOptionController::class, 'update'])->name('question-options.update');
    Route::delete('/question-options/{option}', [QuestionOptionController::class, 'destroy'])->name('question-options.destroy');

    // =========== User Management ===========
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
