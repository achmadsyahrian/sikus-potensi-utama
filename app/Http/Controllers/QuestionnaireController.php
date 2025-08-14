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
        if ($questionnaire->answers()->exists()) {
            return redirect()->back()->with('error', 'Kuesioner tidak dapat dihapus karena sudah memiliki jawaban.');
        }

        $questionnaire->delete();

        return redirect()->route('questionnaires.index')->with('success', 'Kuesioner berhasil dihapus!');
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
        $respondentUserIds = $questionnaire->answers()->select('user_id')
            ->distinct()
            ->pluck('user_id');

        $perPage = 50;
        $currentPage = Paginator::resolveCurrentPage();

        $paginatedUsers = User::whereIn('id', $respondentUserIds)
            ->with(['studentDetail', 'lecturerDetail', 'roles'])
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $userAnswers = $questionnaire->answers()
            ->whereIn('user_id', $paginatedUsers->pluck('id'))
            ->with(['user', 'role'])
            ->get();

        $groupedRespondents = $paginatedUsers->map(function ($user) use ($userAnswers) {
            $answers = $userAnswers->where('user_id', $user->id);
            $roles = $answers->map->role->unique('id');

            return (object)[
                'user' => $user,
                'roles' => $roles,
                'answers' => $answers,
            ];
        });

        return new LengthAwarePaginator(
            $groupedRespondents,
            $paginatedUsers->total(),
            $paginatedUsers->perPage(),
            $paginatedUsers->currentPage(),
            ['path' => Paginator::resolveCurrentPath()]
        );
    }
}
