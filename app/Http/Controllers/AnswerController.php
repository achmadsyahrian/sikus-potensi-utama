<?php
namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Faculty;
use App\Models\ProgramStudy;
use App\Models\Questionnaire;
use App\Models\RespondentExternal;
use App\Services\Sevima\LecturerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class AnswerController extends Controller
{
    public function index()
    {
        $user         = Auth::user();
        $userId       = $user->id;
        $activeRoleId = Session::get('active_role_id');
        $activeRole   = $user->roles->find($activeRoleId);

        $targetIdentifiers = [
            ['target_type' => 'role', 'target_value' => $activeRole->name],
        ];

        if ($activeRole->name === 'Mahasiswa' && $user->studentDetail) {
            $studentDetail = $user->studentDetail;
            $programStudy  = ProgramStudy::where('program_study_code', $studentDetail->program_study_code)->first();
            $faculty       = Faculty::where('faculty_code', $programStudy->faculty_code)->first();

            if ($programStudy) {
                $targetIdentifiers[] = ['target_type' => 'program_study', 'target_value' => $programStudy->id];
            }
            if ($faculty) {
                $targetIdentifiers[] = ['target_type' => 'faculty', 'target_value' => $faculty->id];
            }
        }

        $questionnaires = Questionnaire::query()
            ->with(['targets', 'academicPeriod'])
            ->where('is_active', true)
            ->where(function ($q) use ($targetIdentifiers, $activeRole) {
                if ($activeRole->name !== 'Mahasiswa') {
                    $q->whereHas('targets', function ($subQuery) use ($activeRole) {
                        $subQuery->where('target_type', 'role')
                            ->where('target_value', $activeRole->name);
                    });
                } else {
                    $q->orWhereHas('targets', function ($subQuery) {
                        $subQuery->whereIn('target_type', ['university', 'all']);
                    });

                    $facultyMatch = collect($targetIdentifiers)->where('target_type', 'faculty')->pluck('target_value')->toArray();
                    $prodiMatch   = collect($targetIdentifiers)->where('target_type', 'program_study')->pluck('target_value')->toArray();

                    $q->orWhere(function ($subQ) use ($facultyMatch, $prodiMatch) {
                        $subQ->where(function ($innerQ) use ($facultyMatch, $prodiMatch) {
                            if (! empty($facultyMatch)) {
                                $innerQ->orWhereHas('targets', function ($targetQ) use ($facultyMatch) {
                                    $targetQ->where('target_type', 'faculty')->whereIn('target_value', $facultyMatch);
                                });
                            }
                            if (! empty($prodiMatch)) {
                                $innerQ->orWhereHas('targets', function ($targetQ) use ($prodiMatch) {
                                    $targetQ->where('target_type', 'program_study')->whereIn('target_value', $prodiMatch);
                                });
                            }
                        });
                    });
                }
            })
            ->paginate(15);

        // ── Khusus Dosen ───────────────────────────────────────────────────────
        $isDosen        = $activeRole->slug === 'dosen';
        $lecturerDetail = $isDosen ? $user->lecturerDetail : null;

        if ($isDosen && $lecturerDetail?->sevima_id) {
            // Pre-fetch semua periode unik — maksimal N hit API = jumlah periode unik
            $uniquePeriodSevimaIds = collect($questionnaires->items())
                ->pluck('academicPeriod.sevima_id')
                ->filter()
                ->unique()
                ->values();

            $schedulePerPeriod = [];
            foreach ($uniquePeriodSevimaIds as $sevimaId) {
                $cacheKey                     = "lecturer_schedule_{$userId}_{$sevimaId}";
                $schedulePerPeriod[$sevimaId] = Cache::remember($cacheKey, now()->addHours(1), function () use ($lecturerDetail, $sevimaId) {
                    return LecturerService::fetchLecturerSchedule(
                        $lecturerDetail->sevima_id,
                        $sevimaId
                    );
                });
            }

            // Pre-fetch semua filled prodi sekaligus — 1 query saja
            $questionnaireIds = collect($questionnaires->items())->pluck('id')->toArray();
            $filledProdiMap   = Answer::where('user_id', $userId)
                ->where('role_id', $activeRoleId)
                ->whereIn('questionnaire_id', $questionnaireIds)
                ->whereNotNull('lecturer_program_study_code')
                ->select('questionnaire_id', 'lecturer_program_study_code')
                ->distinct()
                ->get()
                ->groupBy('questionnaire_id')
                ->map(fn($rows) => $rows->pluck('lecturer_program_study_code')->toArray());

            // Pre-fetch degree_level semua prodi sekaligus — 1 query saja
            $allProdiCodes = collect($schedulePerPeriod)
                ->flatMap(fn($schedule) => collect($schedule['data'] ?? [])
                        ->where('attributes.is_deleted', '0')
                        ->pluck('attributes.id_program_studi')
                )
                ->unique()
                ->toArray();

            $localProdiMap = ProgramStudy::whereIn('program_study_code', $allProdiCodes)
                ->pluck('degree_level', 'program_study_code');

            $questionnaires->getCollection()->transform(function ($questionnaire) use (
                $userId, $activeRoleId, $schedulePerPeriod, $filledProdiMap, $localProdiMap
            ) {
                $sevimaId     = $questionnaire->academicPeriod?->sevima_id;
                $scheduleData = $schedulePerPeriod[$sevimaId] ?? null;

                $questionnaire->prodi_info = null;

                if ($scheduleData && ! empty($scheduleData['data'])) {
                    $rawProdi = collect($scheduleData['data'])
                        ->where('attributes.is_deleted', '0')
                        ->map(fn($item) => [
                            'id_program_studi' => $item['attributes']['id_program_studi'],
                            'program_studi'    => $item['attributes']['program_studi'],
                        ])
                        ->unique('id_program_studi')
                        ->values();

                    $filledProdi = $filledProdiMap[$questionnaire->id] ?? [];

                    $prodiList = $rawProdi->map(fn($prodi) => [
                        'id_program_studi' => $prodi['id_program_studi'],
                        'program_studi'    => $prodi['program_studi'],
                        'degree_level'     => $localProdiMap[$prodi['id_program_studi']] ?? null,
                        'is_filled'        => in_array($prodi['id_program_studi'], $filledProdi),
                    ]);

                    $totalProdi  = $prodiList->count();
                    $filledCount = $prodiList->where('is_filled', true)->count();

                    $questionnaire->prodi_info = [
                        'prodi_list'   => $prodiList->toArray(),
                        'total'        => $totalProdi,
                        'filled_count' => $filledCount,
                        'all_filled'   => $filledCount === $totalProdi,
                    ];

                    $questionnaire->status = $filledCount === 0 ? 'Belum Diisi'
                        : ($filledCount < $totalProdi ? 'Sebagian Diisi' : 'Diisi');
                }

                return $questionnaire;
            });
        } else {
            $questionnaires->getCollection()->transform(function ($questionnaire) use ($userId, $activeRoleId) {
                $hasAnswered = DB::table('answers')
                    ->where('user_id', $userId)
                    ->where('questionnaire_id', $questionnaire->id)
                    ->where('role_id', $activeRoleId)
                    ->exists();

                $questionnaire->status     = $hasAnswered ? 'Diisi' : 'Belum Diisi';
                $questionnaire->targetRole = collect($questionnaire->targets)->pluck('target_value')->implode(', ');
                $questionnaire->dueDate    = $questionnaire->end_date;
                $questionnaire->prodi_info = null;

                return $questionnaire;
            });
        }

        return Inertia::render('Answers/Index', [
            'questionnaires' => $questionnaires,
            'isDosen'        => $isDosen,
        ]);
    }

    public function show(Questionnaire $questionnaire)
    {
        $userId       = Auth::id();
        $user         = Auth::user();
        $activeRoleId = Session::get('active_role_id');
        $activeRole   = $user->roles->find($activeRoleId);

        $hasAnswered = Answer::where('questionnaire_id', $questionnaire->id)
            ->where('user_id', $userId)
            ->where('role_id', $activeRoleId)
            ->exists();

        // Untuk non-dosen, cek sudah isi atau belum seperti biasa
        if ($hasAnswered && $activeRole->slug !== 'dosen') {
            return redirect()->back()->with('error', 'Anda sudah mengisi kuesioner ini.');
        }

        $questionnaire->load(['categories.questions', 'questionsWithoutCategory', 'options', 'academicPeriod']);

        $allQuestions = $questionnaire->categories->flatMap(fn($cat) => $cat->questions)
            ->merge($questionnaire->questionsWithoutCategory)
            ->sortBy('order')
            ->values();

        $questionnaire->unsetRelation('categories');
        $questionnaire->unsetRelation('questionsWithoutCategory');

        // ── Khusus Dosen: ambil prodi dari Sevima ─────────────────────────────
        $availableProdi = [];

        if ($activeRole->slug === 'dosen') {
            $lecturerDetail = $user->lecturerDetail;
            $period         = $questionnaire->academicPeriod;

            if ($lecturerDetail?->sevima_id && $period?->sevima_id) {
                $cacheKey     = "lecturer_schedule_{$userId}_{$period->sevima_id}";
                $scheduleData = Cache::remember($cacheKey, now()->addHours(1), function () use ($lecturerDetail, $period) {
                    return LecturerService::fetchLecturerSchedule(
                        $lecturerDetail->sevima_id,
                        $period->sevima_id
                    );
                });

                if ($scheduleData && ! empty($scheduleData['data'])) {
                    $rawProdi = collect($scheduleData['data'])
                        ->where('attributes.is_deleted', '0')
                        ->map(fn($item) => [
                            'id_program_studi' => $item['attributes']['id_program_studi'],
                            'program_studi'    => $item['attributes']['program_studi'],
                        ])
                        ->unique('id_program_studi')
                        ->values();

                    $localProdi = ProgramStudy::whereIn('program_study_code', $rawProdi->pluck('id_program_studi'))
                        ->pluck('degree_level', 'program_study_code');

                    // Tandai prodi mana yang sudah diisi
                    $filledProdi = Answer::where('questionnaire_id', $questionnaire->id)
                        ->where('user_id', $userId)
                        ->where('role_id', $activeRoleId)
                        ->whereNotNull('lecturer_program_study_code')
                        ->pluck('lecturer_program_study_code')
                        ->unique()
                        ->toArray();

                    $availableProdi = $rawProdi->map(fn($prodi) => [
                        'id_program_studi' => $prodi['id_program_studi'],
                        'program_studi'    => $prodi['program_studi'],
                        'degree_level'     => $localProdi[$prodi['id_program_studi']] ?? null,
                        'is_filled'        => in_array($prodi['id_program_studi'], $filledProdi),
                    ])->toArray();
                }
            }

            // Kalau semua prodi sudah diisi → redirect
            if (! empty($availableProdi) && collect($availableProdi)->every(fn($p) => $p['is_filled'])) {
                return redirect()->back()->with('error', 'Anda sudah mengisi kuesioner ini untuk semua program studi yang diampu.');
            }
        }

        return Inertia::render('Answers/Show', [
            'questionnaire'  => $questionnaire,
            'allQuestions'   => $allQuestions,
            'availableProdi' => $availableProdi,
            'isDosen'        => $activeRole->slug === 'dosen',
        ]);
    }

    public function store(Request $request, Questionnaire $questionnaire)
    {
        $data = $request->validate([
            'answers'                     => 'required|array',
            'answers.*.question_id'       => 'required|exists:questions,id',
            'answers.*.answer_value'      => 'nullable',
            'lecturer_program_study_code' => 'nullable|string',
        ]);

        $userId       = Auth::id();
        $activeRoleId = Session::get('active_role_id');
        $prodiCode    = $data['lecturer_program_study_code'] ?? null;

        // Cek kalau dosen sudah isi prodi yang sama
        if ($prodiCode) {
            $alreadyFilled = Answer::where('questionnaire_id', $questionnaire->id)
                ->where('user_id', $userId)
                ->where('role_id', $activeRoleId)
                ->where('lecturer_program_study_code', $prodiCode)
                ->exists();

            if ($alreadyFilled) {
                return redirect()->back()->with('error', 'Anda sudah mengisi kuesioner untuk program studi ini.');
            }
        }

        DB::transaction(function () use ($data, $questionnaire, $userId, $activeRoleId, $prodiCode) {
            foreach ($data['answers'] as $answer) {
                $questionnaire->answers()->create([
                    'user_id'                     => $userId,
                    'question_id'                 => $answer['question_id'],
                    'answer_value'                => $answer['answer_value'],
                    'role_id'                     => $activeRoleId,
                    'lecturer_program_study_code' => $prodiCode,
                ]);
            }
        });

        return redirect()->route('dashboard')->with('success', 'Kuesioner berhasil dikirimkan.');
    }

    public function submitted(Questionnaire $questionnaire)
    {
        $userId       = Auth::id();
        $user         = Auth::user();
        $activeRoleId = Session::get('active_role_id');
        $activeRole   = $user->roles->find($activeRoleId);
        $isDosen      = $activeRole->slug === 'dosen';

        $questionnaire->load(['categories.questions.category', 'options', 'academicPeriod']);

        if ($isDosen) {
            // Ambil daftar prodi yang sudah diisi dosen
            $filledProdi = Answer::where('questionnaire_id', $questionnaire->id)
                ->where('user_id', $userId)
                ->where('role_id', $activeRoleId)
                ->whereNotNull('lecturer_program_study_code')
                ->select('lecturer_program_study_code')
                ->distinct()
                ->pluck('lecturer_program_study_code')
                ->toArray();

            if (empty($filledProdi)) {
                return redirect()->route('answers.index')->with('error', 'Anda belum mengisi kuesioner ini.');
            }

            $localProdi = ProgramStudy::whereIn('program_study_code', $filledProdi)
                ->get(['program_study_code', 'name', 'degree_level'])
                ->keyBy('program_study_code');

            $prodiList = collect($filledProdi)->map(fn($code) => [
                'program_study_code' => $code,
                'program_studi'      => $localProdi[$code]->name ?? $code,
                'degree_level'       => $localProdi[$code]->degree_level ?? null,
            ])->values();

            return Inertia::render('Answers/Submitted', [
                'questionnaire'    => $questionnaire,
                'isDosen'          => true,
                'prodiList'        => $prodiList,
                'submittedAnswers' => [], // kosong, load per prodi via selectedProdi
            ]);
        }

        // Non-dosen: logic lama
        $submittedAnswers = Answer::with(['question.category'])
            ->where('questionnaire_id', $questionnaire->id)
            ->where('user_id', $userId)
            ->where('role_id', $activeRoleId)
            ->get();

        if ($submittedAnswers->isEmpty()) {
            return redirect()->route('answers.index')->with('error', 'Anda belum mengisi kuesioner ini.');
        }

        return Inertia::render('Answers/Submitted', [
            'questionnaire'    => $questionnaire,
            'isDosen'          => false,
            'prodiList'        => [],
            'submittedAnswers' => $submittedAnswers,
        ]);
    }

    public function submittedByProdi(Request $request, Questionnaire $questionnaire)
    {
        $userId       = Auth::id();
        $activeRoleId = Session::get('active_role_id');
        $prodiCode    = $request->input('prodi_code');

        $answers = Answer::with(['question.category'])
            ->where('questionnaire_id', $questionnaire->id)
            ->where('user_id', $userId)
            ->where('role_id', $activeRoleId)
            ->where('lecturer_program_study_code', $prodiCode)
            ->get();

        return response()->json($answers);
    }

    public function storeExternal(Request $request, Questionnaire $questionnaire)
    {
        $validatedData = $request->validate([
            'role'                   => 'required|in:alumni,mitra,pengguna_lulusan',
            'program_study_code'     => 'required|string|exists:program_studies,program_study_code',
            'name'                   => 'required|string|max:255',
            'company_or_institution' => 'required_unless:role,alumni|nullable|string|max:255',
            'contact_number'         => 'required|string|max:255',
            'answers'                => 'required|array',
            'answers.*.question_id'  => 'required|exists:questions,id',
            'answers.*.answer_value' => 'nullable',
        ]);

        DB::transaction(function () use ($validatedData, $questionnaire) {
            $respondentExternal = RespondentExternal::create([
                'questionnaire_id'       => $questionnaire->id,
                'role'                   => $validatedData['role'],
                'program_study_code'     => $validatedData['program_study_code'],
                'name'                   => $validatedData['name'],
                'company_or_institution' => $validatedData['company_or_institution'] ?? null,
                'contact_number'         => $validatedData['contact_number'],
            ]);

            foreach ($validatedData['answers'] as $answerData) {
                if (isset($answerData['answer_value']) && ! is_null($answerData['answer_value']) && $answerData['answer_value'] !== '') {
                    Answer::create([
                        'questionnaire_id'       => $questionnaire->id,
                        'question_id'            => $answerData['question_id'],
                        'answer_value'           => $answerData['answer_value'],
                        'user_id'                => null,
                        'role_id'                => null,
                        'respondent_external_id' => $respondentExternal->id,
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', 'Terima kasih, kuesioner berhasil dikirim!');
    }
}
