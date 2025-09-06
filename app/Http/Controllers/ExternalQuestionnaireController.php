<?php

namespace App\Http\Controllers;

use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExternalQuestionnaireController extends Controller
{
    public function show($token)
    {
        // Cari kuesioner berdasarkan public_link_token
        $questionnaire = Questionnaire::with(['categories.questions', 'questionsWithoutCategory', 'options'])
            ->where('public_link_token', $token)
            ->firstOrFail();

        // Validasi apakah kuesioner aktif dan dalam rentang tanggal
        if (!$questionnaire->is_active || now()->lt($questionnaire->start_date) || now()->gt($questionnaire->end_date)) {
            abort(404, 'Kuesioner tidak aktif atau sudah kadaluarsa.');
        }

        // Muat semua relasi yang diperlukan
        $questionnaire->load(['categories.questions', 'questionsWithoutCategory', 'options']);

        // Buat koleksi tunggal dari semua pertanyaan
        $allQuestions = $questionnaire->categories->flatMap(function ($category) {
            return $category->questions;
        })->merge($questionnaire->questionsWithoutCategory);

        // Sortir koleksi pertanyaan
        $sortedQuestions = $allQuestions->sortBy('order')->values();

        // Kosongkan koleksi pertanyaan asli agar tidak terkirim dua kali
        $questionnaire->unsetRelation('categories');
        $questionnaire->unsetRelation('questionsWithoutCategory');

        // Tampilkan halaman Inertia.js untuk kuesioner publik
        return Inertia::render('ExternalQuestionnaire/Show', [
            'questionnaire' => $questionnaire,
            'allQuestions' => $sortedQuestions,
        ]);
    }
}
