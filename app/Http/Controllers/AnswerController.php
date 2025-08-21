<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Faculty;
use App\Models\ProgramStudy;
use App\Models\Questionnaire;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class AnswerController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userId = $user->id;
        $activeRoleId = Session::get('active_role_id');
        $activeRole = $user->roles->find($activeRoleId);

        // Target array awal hanya dari activeRole
        $targetIdentifiers = [
            ['target_type' => 'role', 'target_value' => $activeRole->name],
        ];

        // Kalau Mahasiswa → tambahkan faculty & program_study
        if ($activeRole->name === 'Mahasiswa' && $user->studentDetails) {
            $studentDetails = $user->studentDetails;
            $programStudy = ProgramStudy::where('program_study_code', $studentDetails->program_study_code)->first();
            $faculty = Faculty::where('faculty_code', $programStudy->faculty_code)->first();

            if ($programStudy) {
                $targetIdentifiers[] = ['target_type' => 'program_study', 'target_value' => $programStudy->id];
            }
            if ($faculty) {
                $targetIdentifiers[] = ['target_type' => 'faculty', 'target_value' => $faculty->id];
            }
        }

        $questionnaires = Questionnaire::query()
            ->with(['targets'])
            ->where('is_active', true)
            ->where(function ($q) use ($targetIdentifiers, $activeRole) {

                // Kalau bukan Mahasiswa → cukup cek role
                if ($activeRole->name !== 'Mahasiswa') {
                    $q->whereHas('targets', function ($subQuery) use ($activeRole) {
                        $subQuery->where('target_type', 'role')
                            ->where('target_value', $activeRole->name);
                    });
                } else {
                    // Mahasiswa → boleh target 'university'/'all'
                    $q->orWhereHas('targets', function ($subQuery) {
                        $subQuery->whereIn('target_type', ['university', 'all']);
                    });

                    // Mahasiswa → harus ada faculty ATAU program_study yang match
                    $facultyMatch = collect($targetIdentifiers)
                        ->where('target_type', 'faculty')
                        ->pluck('target_value')
                        ->toArray();

                    $prodiMatch = collect($targetIdentifiers)
                        ->where('target_type', 'program_study')
                        ->pluck('target_value')
                        ->toArray();

                    $q->orWhere(function ($subQ) use ($facultyMatch, $prodiMatch) {
                        $subQ->where(function ($innerQ) use ($facultyMatch, $prodiMatch) {
                            if (!empty($facultyMatch)) {
                                $innerQ->orWhereHas('targets', function ($targetQ) use ($facultyMatch) {
                                    $targetQ->where('target_type', 'faculty')
                                        ->whereIn('target_value', $facultyMatch);
                                });
                            }
                            if (!empty($prodiMatch)) {
                                $innerQ->orWhereHas('targets', function ($targetQ) use ($prodiMatch) {
                                    $targetQ->where('target_type', 'program_study')
                                        ->whereIn('target_value', $prodiMatch);
                                });
                            }
                        });
                    });
                }
            })
            ->get();

        $questionnaires = $questionnaires->map(function ($questionnaire) use ($userId) {
            $hasAnswered = DB::table('answers')
                ->where('user_id', $userId)
                ->where('questionnaire_id', $questionnaire->id)
                ->where('role_id', Session::get('active_role_id'))
                ->exists();

            $questionnaire->status = $hasAnswered ? 'Diisi' : 'Belum Diisi';
            $questionnaire->targetRole = collect($questionnaire->targets)->pluck('target_value')->implode(', ');
            $questionnaire->dueDate = $questionnaire->end_date;

            return $questionnaire;
        });

        return Inertia::render('Answers/Index', [
            'questionnaires' => $questionnaires,
        ]);
    }


    public function show(Questionnaire $questionnaire)
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

        return Inertia::render('Answers/Show', [
            'questionnaire' => $questionnaire,
            'allQuestions' => $sortedQuestions, // Kirimkan koleksi pertanyaan tunggal
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

    public function submitted(Questionnaire $questionnaire)
    {
        $userId = Auth::id();
        $activeRoleId = Session::get('active_role_id');

        // Ambil semua jawaban user untuk kuesioner dan peran yang sedang aktif
        $submittedAnswers = Answer::with(['question.category'])
            ->where('questionnaire_id', $questionnaire->id)
            ->where('user_id', $userId)
            ->where('role_id', $activeRoleId)
            ->get();

        // Redirect jika belum ada jawaban
        if ($submittedAnswers->isEmpty()) {
            return redirect()->route('answers.index')->with('error', 'Anda belum mengisi kuesioner ini.');
        }

        return Inertia::render('Answers/Submitted', [
            'questionnaire' => $questionnaire->load(['categories.questions.category', 'options']),
            'submittedAnswers' => $submittedAnswers,
        ]);
    }
}
