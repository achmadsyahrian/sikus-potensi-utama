<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionnaireRequest;
use App\Http\Requests\UpdateQuestionnaireRequest;
use App\Models\AcademicPeriod;
use App\Models\Faculty;
use App\Models\ProgramStudy;
use App\Models\Questionnaire;
use App\Models\Role;
use App\Models\User;
use App\Models\SatisfactionCriterion;
use App\Services\QuestionnaireService; // Impor service
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Inertia\Inertia;
use Illuminate\Support\Str;

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

        $questionnaires = $query->paginate(10)->withQueryString();

        return Inertia::render('Questionnaires/Index', [
            'questionnaires' => $questionnaires,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        $academicPeriods = AcademicPeriod::orderBy('name', 'desc')->get();
        $roles = Role::whereNotIn('name', ['superadmin', 'admin'])
            ->orderBy('name', 'asc')
            ->get();
        $faculties = Faculty::orderBy('name', 'asc')->get();
        $programStudies = ProgramStudy::orderBy('name', 'asc')->get();

        return Inertia::render('Questionnaires/Create', [
            'academicPeriods' => $academicPeriods,
            'roles' => $roles,
            'faculties' => $faculties,
            'programStudies' => $programStudies,
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
            'categories.questions.category',
            'options',
            'answers.user.studentDetail',
            'answers.user.lecturerDetail',
            'answers.role',
            'answers.respondentExternal',
        ]);

        $baseData = $this->getBaseViewData();
        $respondents = $this->getRespondentsData($questionnaire);
        $questionCategories = $questionnaire->categories;

        $satisfactionCriteria = SatisfactionCriterion::orderBy('min_value', 'asc')->get();

        return Inertia::render('Questionnaires/Show', array_merge($baseData, [
            'questionnaire' => $questionnaire,
            'questionCategories' => $questionCategories,
            'respondents' => $respondents,
            'satisfactionCriteria' => $satisfactionCriteria,
        ]));
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
        if (!auth()->user()->hasRole('superadmin') && $questionnaire->answers()->exists()) {
            return redirect()->back()->with('error', 'Kuesioner tidak dapat dihapus karena sudah memiliki jawaban.');
        }

        $questionnaire->delete();

        return redirect()->route('questionnaires.index')->with('success', 'Kuesioner berhasil dihapus!');
    }

    public function generatePublicLink(Questionnaire $questionnaire)
    {
        if (!$questionnaire->is_active) {
            return back()->with('error', 'Kuesioner harus aktif untuk dapat membuat tautan publik.');
        }

        if ($questionnaire->public_link_token) {
            return back()->with('error', 'Tautan publik sudah ada.');
        }

        $token = Str::random(32);
        $questionnaire->public_link_token = $token;
        $questionnaire->save();

        return back()->with('success', 'Tautan publik berhasil dibuat.');
    }


    private function getBaseViewData()
    {
        $academicPeriods = AcademicPeriod::orderBy('name', 'desc')->get();
        $roles = Role::whereNotIn('name', ['superadmin', 'admin'])
            ->orderBy('name', 'asc')
            ->get();
        $faculties = Faculty::orderBy('name', 'asc')->get();
        $programStudies = ProgramStudy::orderBy('name', 'asc')->get();

        return compact('academicPeriods', 'roles', 'faculties', 'programStudies');
    }

    private function getRespondentsData(Questionnaire $questionnaire)
    {
        $answers = $questionnaire->answers()
            ->with(['user.studentDetail', 'user.lecturerDetail', 'role', 'respondentExternal'])
            ->get();

        $allRespondents = collect();

        $answers->whereNotNull('user_id')
            ->unique(fn ($item) => $item->user_id . $item->role_id)
            ->each(function ($answer) use ($allRespondents) {
                $user = $answer->user;
                if ($user) {
                    $allRespondents->push([
                        'id' => $user->id,
                        'type' => 'internal',
                        'name' => $user->name,
                        'roles' => $answer->role ? [$answer->role] : [],
                        'user' => $user, // Mengirim objek user utuh (termasuk student_detail)
                    ]);
                }
            });

        $answers->whereNotNull('respondent_external_id')
            ->unique('respondent_external_id')
            ->each(function ($answer) use ($allRespondents) {
                $external = $answer->respondentExternal;
                if ($external) {
                    $allRespondents->push([
                        'id' => $external->id,
                        'type' => 'external',
                        'name' => $external->name,
                        'roles' => [['id' => 0, 'name' => 'Eksternal']],
                        'respondent_external' => $external,
                    ]);
                }
            });

        return [
            'data' => $allRespondents->values(),
            'total' => $allRespondents->count()
        ];
    }

    private function getIdentitas($user)
    {
        $rolesArray = $user->roles ? $user->roles->toArray() : [];
        $isMahasiswa = collect($rolesArray)->some(fn($role) => $role['name'] === 'Mahasiswa');
        $isDosen = collect($rolesArray)->some(fn($role) => $role['name'] === 'Dosen');

        if ($isMahasiswa && $user->studentDetail) {
            return $user->studentDetail->nim;
        }

        if ($isDosen && $user->lecturerDetail) {
            return $user->lecturerDetail->nidn;
        }

        return '-';
    }
}
