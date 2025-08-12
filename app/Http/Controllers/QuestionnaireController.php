<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Faculty;
use App\Models\ProgramStudy;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\Questionnaire;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class QuestionnaireController extends Controller
{
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
        $roles = Role::orderBy('name', 'asc')->get();
        $faculties = Faculty::orderBy('name', 'asc')->get();
        $programStudies = ProgramStudy::orderBy('name', 'asc')->get();

        return Inertia::render('Questionnaires/Create', [
            'academicPeriods' => $academicPeriods,
            'roles' => $roles,
            'faculties' => $faculties,
            'programStudies' => $programStudies,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'is_active' => ['boolean'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'targets' => ['required', 'array'],
            'targets.*.target_type' => ['required', 'string', Rule::in(['role', 'university', 'faculty', 'program_study'])],
            'targets.*.target_value' => ['required', 'max:255'],
        ]);
        DB::transaction(function () use ($request) {
            // 1. Buat kuesioner baru
            $questionnaire = Questionnaire::create([
                'name' => $request->name,
                'description' => $request->description,
                'academic_period_id' => $request->academic_period_id,
                'is_active' => $request->is_active,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            // Ambil semua target yang masuk dari frontend
            $targets = collect($request->targets);

            // 2. Filter target untuk menghapus prodi jika fakultasnya sudah ada
            $finalTargets = $targets->reject(function ($target) use ($targets) {
                if ($target['target_type'] === 'program_study') {
                    $programStudy = ProgramStudy::find($target['target_value']);
                    if ($programStudy) {
                        $faculty = Faculty::where('faculty_code', $programStudy->faculty_code)->first();
                        return $targets->contains(function ($t) use ($faculty) {
                            return $t['target_type'] === 'faculty' && $t['target_value'] == $faculty->id;
                        });
                    }
                }
                return false;
            });

            // 3. Simpan target yang sudah difilter
            foreach ($finalTargets as $target) {
                $questionnaire->targets()->create([
                    'target_type' => $target['target_type'],
                    'target_value' => $target['target_value'],
                ]);
            }
        });

        return redirect()->route('questionnaires.index')->with('success', 'Kuesioner berhasil disimpan!');
    }
    public function show(Questionnaire $questionnaire)
    {
        $academicPeriods = AcademicPeriod::orderBy('name', 'desc')->get();
        $roles = Role::orderBy('name', 'asc')->get();
        $faculties = Faculty::orderBy('name', 'asc')->get();
        $programStudies = ProgramStudy::orderBy('name', 'asc')->get();
        $questionCategories = $questionnaire->categories()->get();

        return Inertia::render('Questionnaires/Show', [
            'questionnaire' => $questionnaire,
            'academicPeriods' => $academicPeriods,
            'roles' => $roles,
            'faculties' => $faculties,
            'programStudies' => $programStudies,
            'questionCategories' => $questionCategories
        ]);
    }

    public function update(Request $request, Questionnaire $questionnaire)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'is_active' => ['boolean'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'targets' => ['required', 'array'],
            'targets.*.target_type' => ['required', 'string', Rule::in(['role', 'university', 'faculty', 'program_study'])],
            'targets.*.target_value' => ['required', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $questionnaire) {
            $questionnaire->update([
                'name' => $request->name,
                'description' => $request->description,
                'academic_period_id' => $request->academic_period_id,
                'is_active' => $request->is_active,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            // Ambil semua target yang masuk dari frontend
            $targets = collect($request->targets);

            // Filter target untuk menghapus prodi jika fakultasnya sudah ada
            $finalTargets = $targets->reject(function ($target) use ($targets) {
                if ($target['target_type'] === 'program_study') {
                    $programStudy = ProgramStudy::find($target['target_value']);
                    if ($programStudy) {
                        $faculty = Faculty::where('faculty_code', $programStudy->faculty_code)->first();
                        return $targets->contains(function ($t) use ($faculty) {
                            return $t['target_type'] === 'faculty' && $t['target_value'] == $faculty->id;
                        });
                    }
                }
                return false;
            });

            // Sinkronisasi target yang sudah difilter
            $questionnaire->targets()->delete();
            foreach ($finalTargets as $target) {
                $questionnaire->targets()->create([
                    'target_type' => $target['target_type'],
                    'target_value' => $target['target_value'],
                ]);
            }
        });

        return redirect()->route('questionnaires.show', $questionnaire->id)->with('success', 'Kuesioner berhasil diperbarui!');
    }
}
