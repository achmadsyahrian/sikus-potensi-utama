<?php

use App\Http\Controllers\AcademicPeriodController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExternalQuestionnaireController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramStudyController;
use App\Http\Controllers\QuestionCategoryController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\QuestionnaireQuestionController;
use App\Http\Controllers\QuestionOptionController;
use App\Http\Controllers\RoleSelectionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Pastikan Anda sudah mendaftarkan middleware 'role' di app/Http/Kernel.php
// Misalnya: 'role' => \App\Http\Middleware\CheckRole::class

Route::middleware('auth')->group(function () {
    Route::get('/role-selection', [RoleSelectionController::class, 'index'])->name('role-selection.index');
    Route::post('/role-selection', [RoleSelectionController::class, 'store'])->name('role-selection.store');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/about-us', function () {
        return Inertia::render('About');
    })->name('about-us');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Rute untuk pengguna yang akan mengisi kuesioner (Jawab Kuesioner)
    // Termasuk role 'mahasiswa', 'dosen', 'pegawai', 'mitra'
    // Route::get('/answers', [AnswerController::class, 'index'])->name('answers.index');


    Route::middleware('role:admin,superadmin')->group(function () {
        // =========== Manajemen Kuesioner ===========
        Route::get('/questionnaires', [QuestionnaireController::class, 'index'])->name('questionnaires.index');
        Route::get('/questionnaires/create', [QuestionnaireController::class, 'create'])->name('questionnaires.create');
        Route::post('/questionnaires', [QuestionnaireController::class, 'store'])->name('questionnaires.store');
        Route::get('/questionnaires/{questionnaire}', [QuestionnaireController::class, 'show'])->name('questionnaires.show');
        Route::put('/questionnaires/{questionnaire}', [QuestionnaireController::class, 'update'])->name('questionnaires.update');
        Route::delete('/questionnaires/{questionnaire}', [QuestionnaireController::class, 'destroy'])->name('questionnaires.destroy');

        Route::post('/questionnaires/{questionnaire}/generate-public-link', [QuestionnaireController::class, 'generatePublicLink'])
            ->name('questionnaires.generatePublicLink');


        // =========== Kategori Kuesioner ===========
        Route::post('/question-categories', [QuestionCategoryController::class, 'store'])->name('question-categories.store');
        Route::put('/question-categories/{category}', [QuestionCategoryController::class, 'update'])->name('question-categories.update');
        Route::delete('/question-categories/{category}', [QuestionCategoryController::class, 'destroy'])->name('question-categories.destroy');

        // =========== Opsi Kuesioner ===========
        Route::post('/question-options', [QuestionOptionController::class, 'store'])->name('question-options.store');
        Route::put('/question-options/{option}', [QuestionOptionController::class, 'update'])->name('question-options.update');
        Route::delete('/question-options/{option}', [QuestionOptionController::class, 'destroy'])->name('question-options.destroy');

        // =========== Pertanyaan Kuesioner ===========
        Route::post('/questionnaires/{questionnaire}/questions', [QuestionController::class, 'store'])->name('questions.store');
        Route::put('/questionnaires/{questionnaire}/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('/questionnaires/{questionnaire}/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');

        // =========== Data Master ===========
        Route::get('/academic-periods', [AcademicPeriodController::class, 'index'])->name('academic-periods.index');
        Route::post('/academic-periods/sync', [AcademicPeriodController::class, 'sync'])->name('academic-periods.sync');

        Route::get('/faculties', [FacultyController::class, 'index'])->name('faculties.index');
        Route::post('/faculties/sync', [FacultyController::class, 'sync'])->name('faculties.sync');

        Route::get('/program-studies', [ProgramStudyController::class, 'index'])->name('program-studies.index');
        Route::post('/program-studies/sync', [ProgramStudyController::class, 'sync'])->name('program-studies.sync');
    });

    Route::middleware('role:superadmin')->group(function () {
        // =========== Pengguna ===========
        Route::resource('/users', UserController::class)->names('users');
        Route::post('/reset-password/{user}', [UserController::class, 'resetPassword'])->name('users.reset-password');
    });

    Route::middleware('role:mahasiswa,dosen,pegawai')->group(function () {
        // =========== List Kuesioner ===========
        Route::get('/questionnaires-list', [AnswerController::class, 'index'])->name('answers.index');

        // =========== Jawaban Kuesioner ===========
        Route::get('/questionnaires/{questionnaire}/answers', [AnswerController::class, 'show'])->name('answers.show');
        Route::post('/questionnaires/{questionnaire}/answers', [AnswerController::class, 'store'])->name('answers.store');
        Route::get('/questionnaires/{questionnaire}/submitted-answers', [AnswerController::class, 'submitted'])->name('answers.submitted');
    });

});

Route::get('/questionnaires/public/{token}', [ExternalQuestionnaireController::class, 'show'])->name('questionnaires.public.show');
Route::post('/answers/store/external/{questionnaire}', [AnswerController::class, 'storeExternal'])->name('answers.store.external');

require __DIR__ . '/auth.php';
