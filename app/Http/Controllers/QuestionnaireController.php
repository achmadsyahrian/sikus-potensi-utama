<?php
namespace App\Http\Controllers;

use App\Exports\RespondentsExport;
use App\Http\Requests\StoreQuestionnaireRequest;
use App\Http\Requests\UpdateQuestionnaireRequest;
use App\Models\AcademicPeriod;
use App\Models\Answer;
use App\Models\Faculty;
use App\Models\ProgramStudy;
use App\Models\Questionnaire;
use App\Models\Role;
use App\Models\User;
use App\Services\QuestionnaireService; // Impor service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class QuestionnaireController extends Controller
{
    protected $questionnaireService;

    public function __construct(QuestionnaireService $questionnaireService)
    {
        $this->questionnaireService = $questionnaireService;
    }

    public function index(Request $request)
    {
        $query = Questionnaire::query()->with('academicPeriod');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $isActive = $request->status === 'active' ? 1 : 0;
            $query->where('is_active', $isActive);
        }

        if ($request->filled('period') && $request->period !== 'all') {
            $query->where('academic_period_id', $request->period);
        }

        $questionnaires = $query->latest()->paginate(15)->withQueryString();

        $academicPeriods = \App\Models\AcademicPeriod::orderBy('name', 'desc')->get(['id', 'name']);

        return Inertia::render('Questionnaires/Index', [
            'questionnaires'  => $questionnaires,
            'academicPeriods' => $academicPeriods,
            'filters'         => $request->only(['search', 'status', 'period']),
        ]);
    }

    public function create()
    {
        $academicPeriods = AcademicPeriod::orderBy('name', 'desc')->get();
        $roles           = Role::whereNotIn('name', ['superadmin', 'admin'])
            ->orderBy('name', 'asc')
            ->get();
        $faculties      = Faculty::orderBy('name', 'asc')->get();
        $programStudies = ProgramStudy::orderBy('name', 'asc')->get();

        return Inertia::render('Questionnaires/Create', [
            'academicPeriods' => $academicPeriods,
            'roles'           => $roles,
            'faculties'       => $faculties,
            'programStudies'  => $programStudies,
        ]);
    }

    public function store(StoreQuestionnaireRequest $request)
    {
        try {
            $this->questionnaireService->createQuestionnaire($request->validated());
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('questionnaires.index')->with('success', 'Kuesioner berhasil disimpan!');
    }

    public function show(Questionnaire $questionnaire)
    {
        $questionnaire->load([
            'targets',
            'academicPeriod',
        ]);

        $baseData = $this->getBaseViewData();

        return Inertia::render('Questionnaires/Show', array_merge($baseData, [
            'questionnaire' => $questionnaire,
        ]));
    }

    public function categories(Questionnaire $questionnaire)
    {
        $questionnaire->load([
            'academicPeriod',
            'categories',
        ]);

        $baseData = $this->getBaseViewData();

        return Inertia::render('Questionnaires/Categories', array_merge($baseData, [
            'questionnaire'      => $questionnaire,
            'questionCategories' => $questionnaire->categories,
        ]));
    }

    public function options(Questionnaire $questionnaire)
    {
        $questionnaire->load([
            'academicPeriod',
            'options',
        ]);

        $baseData = $this->getBaseViewData();

        return Inertia::render('Questionnaires/Options', array_merge($baseData, [
            'questionnaire'   => $questionnaire,
            'questionOptions' => $questionnaire->options,
        ]));
    }

    public function questions(Questionnaire $questionnaire)
    {
        $questionnaire->load([
            'academicPeriod',
            'categories',
            'questions',
        ]);

        $baseData = $this->getBaseViewData();

        return Inertia::render('Questionnaires/Questions', array_merge($baseData, [
            'questionnaire'      => $questionnaire,
            'questionCategories' => $questionnaire->categories,
        ]));
    }

    public function results(Questionnaire $questionnaire)
    {
        $questionnaire->load([
            'academicPeriod',
            'categories',
            'options',
            'questions',
        ]);

        $baseData             = $this->getBaseViewData();
        $satisfactionCriteria = \App\Models\SatisfactionCriterion::orderBy('min_value', 'asc')->get();

        // 1. AGREGASI UNTUK CHART PER-PERTANYAAN
        $questionStatsRaw = DB::table('answers')
            ->where('questionnaire_id', $questionnaire->id)
            ->select('question_id', 'answer_value', DB::raw('count(*) as total'))
            ->groupBy('question_id', 'answer_value')
            ->get();

        // Kelompokkan berdasarkan ID Pertanyaan untuk Frontend
        $questionStats = $questionStatsRaw->groupBy('question_id');

        // 2. AGREGASI UNTUK CHART SUMMARY
        $summaryStats = DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->leftJoin('respondent_externals', 'answers.respondent_external_id', '=', 'respondent_externals.id')
            ->where('answers.questionnaire_id', $questionnaire->id)
            ->where('questions.question_type', 'multiple_choice')
            ->select(
                'questions.category_id',
                'answers.answer_value',
                'answers.role_id',
                'respondent_externals.role as external_role',
                DB::raw('count(*) as total')
            )
            ->groupBy('questions.category_id', 'answers.answer_value', 'answers.role_id', 'respondent_externals.role')
            ->get();

        // 3. AMBIL PREVIEW JAWABAN ESSAY
        $essayQuestions = $questionnaire->questions->where('question_type', '!=', 'multiple_choice');
        $essayPreviews  = [];
        $essayCounts    = [];

        foreach ($essayQuestions as $q) {
            $essayPreviews[$q->id] = Answer::with(['user.student_detail', 'user.lecturer_detail', 'respondent_external', 'role'])
                ->where('question_id', $q->id)
                ->latest()
                ->take(5)
                ->get();
            $essayCounts[$q->id] = Answer::where('question_id', $q->id)->count();
        }

        return Inertia::render('Questionnaires/Results', array_merge($baseData, [
            'questionnaire'        => $questionnaire,
            'satisfactionCriteria' => $satisfactionCriteria,
            'questionStats'        => $questionStats,
            'summaryStats'         => $summaryStats,
            'essayPreviews'        => $essayPreviews,
            'essayCounts'          => $essayCounts,
        ]));
    }

    public function respondents(Request $request, Questionnaire $questionnaire)
    {
        $questionnaire->load(['academicPeriod']);
        $baseData = $this->getBaseViewData();

        $search      = $request->input('search');
        $roleFilter  = $request->input('role', 'all');
        $prodiFilter = $request->input('prodi', 'all');

        $internalRolesCount = DB::table('answers')
            ->join('roles', 'answers.role_id', '=', 'roles.id')
            ->where('answers.questionnaire_id', $questionnaire->id)
            ->whereNotNull('answers.user_id')
            ->select('roles.name', DB::raw('count(distinct answers.user_id) as total'))
            ->groupBy('roles.name')
            ->get();

        $externalRolesCount = DB::table('answers')
            ->join('respondent_externals', 'answers.respondent_external_id', '=', 'respondent_externals.id')
            ->where('answers.questionnaire_id', $questionnaire->id)
            ->whereNotNull('answers.respondent_external_id')
            ->select('respondent_externals.role as name', DB::raw('count(distinct answers.respondent_external_id) as total'))
            ->groupBy('respondent_externals.role')
            ->get();

        $chartStats = $internalRolesCount->concat($externalRolesCount);

        $prodiStats = DB::table('answers')
            ->join('users', 'answers.user_id', '=', 'users.id')
            ->join('student_details', 'users.id', '=', 'student_details.user_id')
            ->join('program_studies', 'student_details.program_study_code', '=', 'program_studies.program_study_code')
            ->where('answers.questionnaire_id', $questionnaire->id)
            ->whereNotNull('answers.user_id')
            ->select(
                'program_studies.name as prodi_name',
                'program_studies.degree_level',
                DB::raw('COUNT(DISTINCT answers.user_id) as total')
            )
            ->groupBy('program_studies.program_study_code', 'program_studies.name', 'program_studies.degree_level')
            ->orderByDesc('total')
            ->get();

        $internalQuery = DB::table('answers')
            ->join('users', 'answers.user_id', '=', 'users.id')
            ->leftJoin('roles', 'answers.role_id', '=', 'roles.id')
            ->leftJoin('student_details', 'users.id', '=', 'student_details.user_id')
            ->leftJoin('lecturer_details', 'users.id', '=', 'lecturer_details.user_id')
            ->leftJoin('program_studies', 'student_details.program_study_code', '=', 'program_studies.program_study_code')
            ->where('answers.questionnaire_id', $questionnaire->id)
            ->whereNotNull('answers.user_id');

        $externalQuery = DB::table('answers')
            ->join('respondent_externals', 'answers.respondent_external_id', '=', 'respondent_externals.id')
            ->where('answers.questionnaire_id', $questionnaire->id)
            ->whereNotNull('answers.respondent_external_id');

        if (! empty($search)) {
            $internalQuery->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('student_details.nim', 'like', "%{$search}%")
                    ->orWhere('lecturer_details.nidn', 'like', "%{$search}%");
            });

            $externalQuery->where(function ($q) use ($search) {
                $q->where('respondent_externals.name', 'like', "%{$search}%")
                    ->orWhere('respondent_externals.company_or_institution', 'like', "%{$search}%")
                    ->orWhere('respondent_externals.contact_number', 'like', "%{$search}%");
            });
        }

        if ($prodiFilter !== 'all') {
            $internalQuery->where('student_details.program_study_code', $prodiFilter);
            $externalQuery->whereRaw('1 = 0');
        }

        if ($roleFilter !== 'all') {
            $validExternalRoles = ['alumni', 'mitra', 'pengguna_lulusan'];

            if (in_array($roleFilter, $validExternalRoles)) {
                $externalQuery->where('respondent_externals.role', $roleFilter);
                $internalQuery->whereRaw('1 = 0');
            } else {
                $internalQuery->where('roles.name', $roleFilter);
                $externalQuery->whereRaw('1 = 0');
            }
        }

        $internalQuery->select(
            'users.id', 'users.name', 'roles.name as role_name',
            'student_details.nim', 'lecturer_details.nidn', 'student_details.program_study_code',
            'program_studies.degree_level',
            DB::raw('NULL as company_or_institution'), DB::raw('NULL as contact_number'),
            DB::raw('"internal" as type')
        )->distinct();

        $externalQuery->select(
            'respondent_externals.id', 'respondent_externals.name', 'respondent_externals.role as role_name',
            DB::raw('NULL as nim'), DB::raw('NULL as nidn'), DB::raw('NULL as program_study_code'),
            DB::raw('NULL as degree_level'),
            'respondent_externals.company_or_institution', 'respondent_externals.contact_number',
            DB::raw('"external" as type')
        )->distinct();

        $searchResults = $internalQuery->union($externalQuery)->limit(50)->get();

        return Inertia::render('Questionnaires/Respondents', array_merge($baseData, [
            'questionnaire' => $questionnaire,
            'respondents'   => $searchResults,
            'chartStats'    => $chartStats,
            'prodiStats'    => $prodiStats,
            'filters'       => [
                'search' => $search,
                'role'   => $roleFilter,
                'prodi'  => $prodiFilter,
            ],
        ]));
    }

    public function getRespondentAnswers(Questionnaire $questionnaire, $type, $id)
    {
        $answersQuery = \App\Models\Answer::with(['role'])
            ->where('questionnaire_id', $questionnaire->id);

        if ($type === 'internal') {
            $answersQuery->where('user_id', $id);
            // UBAH DI SINI: Gunakan camelCase sesuai dengan nama function di Model User.php
            $respondent = \App\Models\User::with(['studentDetail', 'lecturerDetail'])->find($id);
        } else {
            $answersQuery->where('respondent_external_id', $id);
            $respondent = \App\Models\RespondentExternal::find($id);
        }

        $answers = $answersQuery->get();

        return response()->json([
            'respondent' => $respondent,
            'answers'    => $answers,
        ]);
    }

    public function update(UpdateQuestionnaireRequest $request, Questionnaire $questionnaire)
    {
        try {
            $this->questionnaireService->updateQuestionnaire($questionnaire, $request->validated());
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('questionnaires.show', $questionnaire->id)->with('success', 'Kuesioner berhasil diperbarui!');
    }

    public function destroy(Questionnaire $questionnaire)
    {
        if (! auth()->user()->hasRole('superadmin') && $questionnaire->answers()->exists()) {
            return redirect()->back()->with('error', 'Kuesioner tidak dapat dihapus karena sudah memiliki jawaban.');
        }

        $questionnaire->delete();

        return redirect()->route('questionnaires.index')->with('success', 'Kuesioner berhasil dihapus!');
    }

    public function generatePublicLink(Questionnaire $questionnaire)
    {
        if (! $questionnaire->is_active) {
            return back()->with('error', 'Kuesioner harus aktif untuk dapat membuat tautan publik.');
        }

        if ($questionnaire->public_link_token) {
            return back()->with('error', 'Tautan publik sudah ada.');
        }

        $token                            = Str::random(32);
        $questionnaire->public_link_token = $token;
        $questionnaire->save();

        return back()->with('success', 'Tautan publik berhasil dibuat.');
    }

    private function getBaseViewData()
    {
        $academicPeriods = AcademicPeriod::orderBy('name', 'desc')->get();
        $roles           = Role::whereNotIn('name', ['superadmin', 'admin'])
            ->orderBy('name', 'asc')
            ->get();
        $faculties      = Faculty::orderBy('name', 'asc')->get();
        $programStudies = DB::table('program_studies')
        // ->where('is_active', true)
            ->select('program_study_code', 'name', 'degree_level')
            ->orderBy('name', 'asc')
            ->get();

        return compact('academicPeriods', 'roles', 'faculties', 'programStudies');
    }

    private function getRespondentsData(Questionnaire $questionnaire, int $perPage = 20)
    {
        // Internal respondents — query langsung, paginate di DB
        $internalQuery = \DB::table('answers')
            ->join('users', 'answers.user_id', '=', 'users.id')
            ->leftJoin('roles', 'answers.role_id', '=', 'roles.id')
            ->leftJoin('student_details', 'users.id', '=', 'student_details.user_id')
            ->leftJoin('lecturer_details', 'users.id', '=', 'lecturer_details.user_id')
            ->where('answers.questionnaire_id', $questionnaire->id)
            ->whereNotNull('answers.user_id')
            ->select(
                'users.id',
                'users.name',
                'roles.name as role_name',
                'student_details.nim',
                'lecturer_details.nidn',
                \DB::raw('"internal" as type')
            )
            ->distinct(); // unik per user+role

        // External respondents
        $externalQuery = \DB::table('answers')
            ->join('respondent_externals', 'answers.respondent_external_id', '=', 'respondent_externals.id')
            ->where('answers.questionnaire_id', $questionnaire->id)
            ->whereNotNull('answers.respondent_external_id')
            ->select(
                'respondent_externals.id',
                'respondent_externals.name',
                \DB::raw('"Eksternal" as role_name'),
                \DB::raw('NULL as nim'),
                \DB::raw('NULL as nidn'),
                \DB::raw('"external" as type')
            )
            ->distinct();

        // Union keduanya lalu paginate
        $paginator = $internalQuery
            ->union($externalQuery)
            ->paginate($perPage);

        return [
            'data'         => $paginator->items(),
            'total'        => $paginator->total(),
            'links'        => $paginator->linkCollection(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ];
    }

    private function getQuestionnaireStats(Questionnaire $questionnaire)
    {
        $maxScale = \DB::table('question_options')
            ->where('questionnaire_id', $questionnaire->id)
            ->max('option_value') ?: 4;

        $stats = \DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->where('answers.questionnaire_id', $questionnaire->id)
            ->where('questions.question_type', 'multiple_choice')
            ->selectRaw('SUM(answers.answer_value) as total_score, COUNT(answers.id) as total_answers')
            ->first();

        $percentage = 0;
        if ($stats->total_answers > 0) {
            $percentage = round(($stats->total_score / ($stats->total_answers * $maxScale)) * 100, 1);
        }

        $totalRespondents = \DB::table('answers')
            ->where('questionnaire_id', $questionnaire->id)
            ->distinct('user_id')
            ->count('user_id');

        return [
            'total_answers'     => $stats->total_answers ?? 0,
            'total_respondents' => $totalRespondents,
            'percentage'        => $percentage,
            'max_scale'         => $maxScale,
        ];
    }

    private function getIdentitas($user)
    {
        $rolesArray  = $user->roles ? $user->roles->toArray() : [];
        $isMahasiswa = collect($rolesArray)->some(fn($role) => $role['name'] === 'Mahasiswa');
        $isDosen     = collect($rolesArray)->some(fn($role) => $role['name'] === 'Dosen');

        if ($isMahasiswa && $user->studentDetail) {
            return $user->studentDetail->nim;
        }

        if ($isDosen && $user->lecturerDetail) {
            return $user->lecturerDetail->nidn;
        }

        return '-';
    }

    public function getAnalysisData(Request $request, Questionnaire $questionnaire)
    {
        $filterRole     = $request->get('role', 'all');
        $filterProdi    = $request->get('prodi', 'all');
        $filterCategory = $request->get('category', 'all');

        $maxScale = \DB::table('question_options')
            ->where('questionnaire_id', $questionnaire->id)
            ->max('option_value') ?: 4;

        // ── Skor Per Kategori ──────────────────────────────────────────
        $categoryStats = [];
        foreach ($questionnaire->categories as $category) {
            $query = \DB::table('answers')
                ->join('questions', 'answers.question_id', '=', 'questions.id')
                ->where('answers.questionnaire_id', $questionnaire->id)
                ->where('questions.category_id', $category->id)
                ->where('questions.question_type', 'multiple_choice');

            // Filter role
            if ($filterRole !== 'all') {
                if ($filterRole === 'external_all') {
                    $query->whereNotNull('answers.respondent_external_id');
                } elseif (in_array($filterRole, ['alumni', 'mitra', 'pengguna_lulusan'])) {
                    $query->join('respondent_externals', 'answers.respondent_external_id', '=', 'respondent_externals.id')
                        ->where('respondent_externals.role', $filterRole);
                } else {
                    $query->where('answers.role_id', $filterRole);
                }
            }

            $stats = $query->selectRaw('SUM(answers.answer_value) as total_score, COUNT(answers.id) as total_answers')->first();

            if ($stats->total_answers > 0) {
                $pct             = round(($stats->total_score / ($stats->total_answers * $maxScale)) * 100, 1);
                $categoryStats[] = [
                    'id'    => $category->id,
                    'name'  => $category->name,
                    'score' => $pct,
                ];
            }
        }

        // ── Skor Per Prodi (Mahasiswa) ─────────────────────────────────
        $prodiStats = [];
        $mhsRole    = \App\Models\Role::where('name', 'Mahasiswa')->first();

        if ($mhsRole) {
            if ($filterProdi !== 'all') {
                // Detail: skor per kategori untuk 1 prodi
                foreach ($questionnaire->categories as $category) {
                    $stats = \DB::table('answers')
                        ->join('questions', 'answers.question_id', '=', 'questions.id')
                        ->join('student_details', 'answers.user_id', '=', 'student_details.user_id')
                        ->where('answers.questionnaire_id', $questionnaire->id)
                        ->where('questions.category_id', $category->id)
                        ->where('questions.question_type', 'multiple_choice')
                        ->where('answers.role_id', $mhsRole->id)
                        ->where('student_details.program_study_code', $filterProdi)
                        ->selectRaw('SUM(answers.answer_value) as total_score, COUNT(answers.id) as total_answers')
                        ->first();

                    if ($stats->total_answers > 0) {
                        $pct          = round(($stats->total_score / ($stats->total_answers * $maxScale)) * 100, 1);
                        $prodiStats[] = ['name' => $category->name, 'score' => $pct];
                    }
                }
            } else {
                // Banding: skor per prodi (bisa filter kategori)
                $query = \DB::table('answers')
                    ->join('questions', 'answers.question_id', '=', 'questions.id')
                    ->join('student_details', 'answers.user_id', '=', 'student_details.user_id')
                    ->join('program_studies', 'student_details.program_study_code', '=', 'program_studies.program_study_code')
                    ->where('answers.questionnaire_id', $questionnaire->id)
                    ->where('answers.role_id', $mhsRole->id)
                    ->where('questions.question_type', 'multiple_choice');

                if ($filterCategory !== 'all') {
                    $query->where('questions.category_id', $filterCategory);
                }

                $results = $query
                    ->select('program_studies.name as prodi_name', 'student_details.program_study_code')
                    ->selectRaw('SUM(answers.answer_value) as total_score, COUNT(answers.id) as total_answers')
                    ->groupBy('student_details.program_study_code', 'program_studies.name')
                    ->get();

                foreach ($results as $row) {
                    $pct          = round(($row->total_score / ($row->total_answers * $maxScale)) * 100, 1);
                    $prodiStats[] = [
                        'name'  => $row->prodi_name,
                        'code'  => $row->program_study_code,
                        'score' => $pct,
                    ];
                }
            }
        }

        // ── Global Score ───────────────────────────────────────────────
        $globalQuery = \DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->where('answers.questionnaire_id', $questionnaire->id)
            ->where('questions.question_type', 'multiple_choice');

        // 1. Jika di Tab Analisis Aspek (Category)
        if (request()->get('tab') === 'category') {
            if ($filterRole !== 'all') {
                if ($filterRole === 'external_all') {
                    $globalQuery->whereNotNull('answers.respondent_external_id');
                } elseif (in_array($filterRole, ['alumni', 'mitra', 'pengguna_lulusan'])) {
                    $globalQuery->join('respondent_externals', 'answers.respondent_external_id', '=', 'respondent_externals.id')
                        ->where('respondent_externals.role', $filterRole);
                } else {
                    $globalQuery->where('answers.role_id', $filterRole);
                }
            }
        }

        // 2. Jika di Tab Analisis Prodi
        if (request()->get('tab') === 'prodi') {
            // Pastikan hanya mengambil data Mahasiswa
            if ($mhsRole) {
                $globalQuery->where('answers.role_id', $mhsRole->id);
            }

            // Filter Prodi Spesifik
            if ($filterProdi !== 'all') {
                $globalQuery->join('student_details', 'answers.user_id', '=', 'student_details.user_id')
                    ->where('student_details.program_study_code', $filterProdi);
            }

            // Filter Kategori/Aspek Spesifik
            if ($filterCategory !== 'all') {
                $globalQuery->where('questions.category_id', $filterCategory);
            }
        }

        $globalStats = $globalQuery
            ->selectRaw('SUM(answers.answer_value) as total_score, COUNT(answers.id) as total_answers')
            ->first();

        $globalScore = 0;
        if ($globalStats->total_answers > 0) {
            $globalScore = round(($globalStats->total_score / ($globalStats->total_answers * $maxScale)) * 100, 1);
        }

        return response()->json([
            'categoryStats' => $categoryStats,
            'prodiStats'    => $prodiStats,
            'globalScore'   => $globalScore,
            'maxScale'      => $maxScale,
        ]);
    }

    public function exportRespondents(Questionnaire $questionnaire)
    {
        $fileName = 'Responden_' . str_replace(' ', '_', $questionnaire->name) . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new RespondentsExport($questionnaire->id), $fileName);
    }
}
