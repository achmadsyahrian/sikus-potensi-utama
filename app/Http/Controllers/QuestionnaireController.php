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
            'answers.user',
            'answers.user.studentDetail',
            'answers.user.lecturerDetail',
            'answers.user.roles',
            'answers.role',
            'answers.respondentExternal',
        ]);

        $baseData = $this->getBaseViewData();
        $respondents = $this->getPaginatedRespondents($questionnaire);
        $questionCategories = $questionnaire->categories()->get();

        return Inertia::render('Questionnaires/Show', array_merge($baseData, [
            'questionnaire' => $questionnaire,
            'questionCategories' => $questionCategories,
            'respondents' => $respondents,
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

    private function getPaginatedRespondents(Questionnaire $questionnaire)
    {
        $userAnswers = $questionnaire->answers()
            ->with(['user.roles', 'user.studentDetail', 'user.lecturerDetail', 'respondentExternal'])
            ->get();

        $allRespondents = collect();

        // Ambil responden internal yang unik
        $userAnswers->whereNotNull('user_id')->unique('user_id')->each(function ($answer) use ($allRespondents) {
            $user = $answer->user;
            if ($user) {
                $allRespondents->push((object)[
                    'id' => $user->id,
                    'type' => 'internal',
                    'name' => $user->name,
                    'roles' => $user->roles,
                    'details' => $this->getIdentitas($user)
                ]);
            }
        });

        // Ambil responden eksternal yang unik
        $userAnswers->whereNotNull('respondent_external_id')->unique('respondent_external_id')->each(function ($answer) use ($allRespondents) {
            $external = $answer->respondentExternal;
            if ($external) {
                $allRespondents->push((object)[
                    'id' => $external->id,
                    'type' => 'external',
                    'name' => $external->name,
                    'roles' => [(object)['name' => 'Eksternal']],
                    'details' => $external->company_or_institution
                ]);
            }
        });

        // Terapkan paginasi manual pada koleksi gabungan
        $perPage = 50;
        $currentPage = Paginator::resolveCurrentPage();
        $offset = ($currentPage - 1) * $perPage;
        $paginatedItems = $allRespondents->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $paginatedItems,
            $allRespondents->count(),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );
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
