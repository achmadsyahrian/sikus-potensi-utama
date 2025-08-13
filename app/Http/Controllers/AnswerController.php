<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class AnswerController extends Controller
{
    public function index(Questionnaire $questionnaire)
    {
        $userId = Auth::id();
        $activeRoleId = Session::get('active_role_id');
        
        $hasAnswered = Answer::where('questionnaire_id', $questionnaire->id)
            ->where('user_id', $userId)
            ->where('role_id', $activeRoleId)
            ->exists();

        if ($hasAnswered) {
            return redirect()->back()->with('error', 'Anda sudah mengisi kuesioner ini.');
        }

        return Inertia::render('Answers/Index', [
            'questionnaire' => $questionnaire->load(['categories.questions']),
            'roles' => ['dosen', 'pegawai'],
        ]);
    }

    public function store(Request $request, Questionnaire $questionnaire)
    {
        $data = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer_value' => 'nullable',
        ]);

        $userId = Auth::id();
        $activeRoleId = Session::get('active_role_id');
        DB::transaction(function () use ($data, $questionnaire, $userId, $activeRoleId) {
            $questionnaire->answers()
                ->where('user_id', $userId)
                ->where('role_id', $activeRoleId)
                ->delete();

            foreach ($data['answers'] as $answer) {
                $questionnaire->answers()->create([
                    'user_id' => $userId,
                    'question_id' => $answer['question_id'],
                    'answer_value' => $answer['answer_value'],
                    'role_id' => $activeRoleId,
                ]);
            }
        });

        return redirect()->route('dashboard')->with('success', 'Kuesioner berhasil dikirimkan.');
    }
}
