<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Faculty;
use App\Models\ProgramStudy;
use App\Models\Questionnaire;
use App\Models\Role;
use App\Models\SatisfactionCriterion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user         = $request->user();
        $activeRoleId = Session::get('active_role_id');
        $activeRole   = $user->roles->find($activeRoleId);

        if (! $activeRole && $user->roles->count() > 0) {
            $activeRole = $user->roles->first();
            Session::put('active_role_id', $activeRole->id);
        }

        if ($activeRole && in_array($activeRole->slug, ['superadmin', 'admin'])) {
            return $this->adminIndex($request, $activeRole);
        }

        if ($activeRole) {
            return $this->userIndex($request, $activeRole);
        }

        return Inertia::render('Dashboard');
    }

    protected function adminIndex(Request $request, $activeRole)
    {
        $totalQuestionnairesCount  = Questionnaire::count();
        $activeQuestionnairesCount = Questionnaire::where('is_active', true)->count();
        $totalResponsesCount       = DB::table('answers')->count();
        $totalProgramStudiesCount  = ProgramStudy::count();
        $totalFacultiesCount       = Faculty::count();
        $totalAcademicPeriodsCount = AcademicPeriod::count();

        $totalUsersCount       = ($activeRole->slug === 'superadmin') ? User::count() : null;
        $totalLocalUsersCount  = ($activeRole->slug === 'superadmin') ? User::where('auth_provider', 'local')->count() : null;
        $totalSevimaUsersCount = ($activeRole->slug === 'superadmin') ? User::where('auth_provider', 'sevima')->count() : null;

        // ─── Responses By Faculty ──────────────────────────────────────────────
        $totalResponsesByFaculty = DB::table('answers')
            ->join('student_details', 'answers.user_id', '=', 'student_details.user_id')
            ->join('program_studies', 'student_details.program_study_code', '=', 'program_studies.program_study_code')
            ->join('faculties', 'program_studies.faculty_code', '=', 'faculties.faculty_code')
            ->select('faculties.name as faculty_name', DB::raw('COUNT(answers.id) as total'))
            ->groupBy('faculties.faculty_code', 'faculties.name')
            ->get();

        // ─── Monthly Responses By Role ─────────────────────────────────────────
        $responsesByRoleAndMonth = DB::table('answers')
            ->leftJoin('roles', 'answers.role_id', '=', 'roles.id')
            ->select(
                DB::raw('COALESCE(roles.name, "Eksternal") as role_name'),
                DB::raw('DATE_FORMAT(answers.created_at, "%Y-%m") as month'),
                DB::raw('COUNT(answers.id) as total')
            )
            ->groupBy('role_name', 'month')
            ->orderBy('month')
            ->get();

        $roles   = Role::whereNotIn('slug', ['superadmin', 'admin'])->pluck('name')->toArray();
        $roles[] = 'Eksternal';
        $months  = $responsesByRoleAndMonth->pluck('month')->unique()->sort()->values()->toArray();

        $monthlyResponses = collect($roles)->map(function ($roleName) use ($responsesByRoleAndMonth, $months) {
            return [
                'name' => $roleName,
                'data' => collect($months)->map(function ($month) use ($responsesByRoleAndMonth, $roleName) {
                    $found = $responsesByRoleAndMonth
                        ->where('role_name', $roleName)
                        ->where('month', $month)
                        ->first();
                    return $found ? $found->total : 0;
                })->toArray(),
            ];
        })->toArray();

        // ─── Responses By Program Study ────────────────────────────────────────
        $totalResponsesByProgramStudy = DB::table('answers')
            ->join('student_details', 'answers.user_id', '=', 'student_details.user_id')
            ->join('program_studies', 'student_details.program_study_code', '=', 'program_studies.program_study_code')
            ->select('program_studies.name as program_study_name', DB::raw('COUNT(answers.id) as total'))
            ->groupBy('program_studies.program_study_code', 'program_studies.name')
            ->get();

        // ─── Responses By Role ─────────────────────────────────────────────────
        $totalResponsesByRole = DB::table('answers')
            ->leftJoin('roles', 'answers.role_id', '=', 'roles.id')
            ->select(
                DB::raw('COALESCE(roles.name, "Eksternal") as role_name'),
                DB::raw('COUNT(answers.id) as total')
            )
            ->groupBy('role_name')
            ->get();

        // ─── Latest Questionnaires ─────────────────────────────────────────────
        $latestQuestionnaires = Questionnaire::with('academicPeriod')
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'start_date', 'end_date', 'academic_period_id']);

        // ─── Global Satisfaction Trend (OPTIMIZED) ─────────────────────────────
        // Semua kalkulasi dilakukan di sisi DB, tidak ada loop PHP yang query lagi
        $satisfactionRaw = DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->join('questionnaires', 'answers.questionnaire_id', '=', 'questionnaires.id')
            ->join('academic_periods', 'questionnaires.academic_period_id', '=', 'academic_periods.id')
            ->joinSub(
                DB::table('question_options')
                    ->select('questionnaire_id', DB::raw('MAX(option_value) as max_scale'))
                    ->groupBy('questionnaire_id'),
                'qo_max',
                'answers.questionnaire_id', '=', 'qo_max.questionnaire_id'
            )
            ->where('questions.question_type', 'multiple_choice')
            ->select(
                'academic_periods.id as period_id',
                'academic_periods.name as period_name',
                DB::raw('SUM(answers.answer_value) as total_score'),
                DB::raw('SUM(qo_max.max_scale) as total_max')
            )
            ->groupBy('academic_periods.id', 'academic_periods.name')
            ->orderBy('academic_periods.name', 'asc')
            ->get();

        $globalSatisfactionTrend = $satisfactionRaw
            ->map(function ($row) {
                $percentage = $row->total_max > 0
                    ? round(($row->total_score / $row->total_max) * 100, 1)
                    : 0;
                return [
                    'period_name'   => $row->period_name,
                    'average_score' => $percentage,
                ];
            })
            ->filter(fn($item) => $item['average_score'] > 0)
            ->values();

        // ─── Top Questionnaires Stats (OPTIMIZED) ──────────────────────────────
        // Ambil 5 kuesioner aktif terbaru
        $topQuestionnaires = Questionnaire::with('academicPeriod')
            // ->where('is_active', true)
            ->latest()
            ->take(5)
            ->get();

        $topIds = $topQuestionnaires->pluck('id')->toArray();

        // Satu query untuk semua stats sekaligus (tidak per-item)
        $statsPerQuestionnaire = DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->joinSub(
                DB::table('question_options')
                    ->select('questionnaire_id', DB::raw('MAX(option_value) as max_scale'))
                    ->whereIn('questionnaire_id', $topIds)
                    ->groupBy('questionnaire_id'),
                'qo_max',
                'answers.questionnaire_id', '=', 'qo_max.questionnaire_id'
            )
            ->whereIn('answers.questionnaire_id', $topIds)
            ->where('questions.question_type', 'multiple_choice')
            ->select(
                'answers.questionnaire_id',
                DB::raw('SUM(answers.answer_value) as total_score'),
                DB::raw('COUNT(answers.id) as total_answers'),
                DB::raw('MAX(qo_max.max_scale) as max_scale')
            )
            ->groupBy('answers.questionnaire_id')
            ->get()
            ->keyBy('questionnaire_id'); // key by id supaya mudah di-lookup

        // Total responses per questionnaire (count distinct answers, bukan hanya MC)
        $totalResponsesPerQ = DB::table('answers')
            ->whereIn('questionnaire_id', $topIds)
            ->select('questionnaire_id', DB::raw('COUNT(id) as total'))
            ->groupBy('questionnaire_id')
            ->get()
            ->keyBy('questionnaire_id');

        $criteria = SatisfactionCriterion::orderBy('min_value', 'asc')->get();

        $topQuestionnairesStats = $topQuestionnaires->map(function ($q) use ($statsPerQuestionnaire, $totalResponsesPerQ, $criteria) {
            $stat = $statsPerQuestionnaire->get($q->id);

            $percentage = 0;
            if ($stat && $stat->total_answers > 0 && $stat->max_scale > 0) {
                $percentage = round(($stat->total_score / ($stat->total_answers * $stat->max_scale)) * 100, 1);
            }

            $matchedCriterion = $criteria->first(fn($c) => $percentage >= $c->min_value && $percentage <= $c->max_value);

            $totalResp = $totalResponsesPerQ->get($q->id);

            return [
                'id'              => $q->id,
                'name'            => $q->name,
                'period'          => $q->academicPeriod->name,
                'total_responses' => $totalResp ? $totalResp->total : 0,
                'percentage'      => $percentage,
                'label'           => $matchedCriterion?->label ?? 'N/A',
                'color'           => $matchedCriterion?->color ?? '#6c757d',
                'status'          => now()->between($q->start_date, $q->end_date) ? 'Active' : 'Ended',
            ];
        });

        return Inertia::render('Dashboard/Admin', [
            'activeRole'                   => $activeRole->name,
            'userName'                     => $request->user()->name,
            'activeRoleSlug'               => $activeRole->slug,
            'totalQuestionnairesCount'     => $totalQuestionnairesCount,
            'activeQuestionnairesCount'    => $activeQuestionnairesCount,
            'totalResponsesCount'          => $totalResponsesCount,
            'totalProgramStudiesCount'     => $totalProgramStudiesCount,
            'totalFacultiesCount'          => $totalFacultiesCount,
            'totalAcademicPeriodsCount'    => $totalAcademicPeriodsCount,
            'totalUsersCount'              => $totalUsersCount,
            'totalLocalUsersCount'         => $totalLocalUsersCount,
            'totalSevimaUsersCount'        => $totalSevimaUsersCount,
            'latestQuestionnaires'         => $latestQuestionnaires,
            'totalResponsesByFaculty'      => $totalResponsesByFaculty,
            'monthlyResponses'             => $monthlyResponses,
            'monthlyLabels'                => $months,
            'totalResponsesByProgramStudy' => $totalResponsesByProgramStudy,
            'totalResponsesByRole'         => $totalResponsesByRole,
            'globalSatisfactionTrend'      => $globalSatisfactionTrend,
            'topQuestionnairesStats'       => $topQuestionnairesStats,
        ]);
    }

    protected function userIndex(Request $request, $activeRole)
    {
        $user   = $request->user();
        $userId = $user->id;

        $questionnaires = Questionnaire::query()
            ->with('targets')
            ->where('is_active', true)
            ->where(function ($q) use ($activeRole, $user) {
                // Logika untuk peran Dosen, Pegawai, dll.
                if ($activeRole->name !== 'Mahasiswa') {
                    $q->whereHas('targets', function ($subQuery) use ($activeRole) {
                        $subQuery->where('target_type', 'role')
                            ->where('target_value', $activeRole->name);
                    });
                } else {
                    // Logika khusus untuk Mahasiswa
                    $q->whereHas('targets', function ($subQuery) use ($user) {
                        $studentDetails = $user->studentDetail;
                        $programStudyId = null;
                        $facultyId      = null;

                        if ($studentDetails && $studentDetails->program_study_code) {
                            $programStudy = ProgramStudy::where('program_study_code', $studentDetails->program_study_code)->first();
                            if ($programStudy) {
                                $programStudyId = (string) $programStudy->id;
                                if ($programStudy->faculty_code) {
                                    $faculty = Faculty::where('faculty_code', $programStudy->faculty_code)->first();
                                    if ($faculty) {
                                        $facultyId = (string) $faculty->id;
                                    }
                                }
                            }
                        }

                        $subQuery->where(function ($innerQuery) use ($programStudyId, $facultyId) {
                            // Target untuk peran mahasiswa
                            // $innerQuery->orWhere(function ($q) {
                            //     $q->where('target_type', 'role')
                            //     ->where('target_value', 'Mahasiswa');
                            // });

                            // Target untuk program studi
                            if ($programStudyId) {
                                $innerQuery->orWhere(function ($q) use ($programStudyId) {
                                    $q->where('target_type', 'program_study')
                                        ->where('target_value', $programStudyId);
                                });
                            }

                            // Target untuk fakultas
                            if ($facultyId) {
                                $innerQuery->orWhere(function ($q) use ($facultyId) {
                                    $q->where('target_type', 'faculty')
                                        ->where('target_value', $facultyId);
                                });
                            }

                            // Target untuk semua
                            $innerQuery->orWhereIn('target_type', ['university', 'all']);
                        });
                    });
                }
            })
            ->get();

        $completedQuestionnairesCount   = 0;
        $uncompletedQuestionnairesCount = 0;

        $questionnaires = $questionnaires->map(function ($questionnaire) use ($userId, &$completedQuestionnairesCount, &$uncompletedQuestionnairesCount) {
            $hasAnswered = DB::table('answers')
                ->where('user_id', $userId)
                ->where('questionnaire_id', $questionnaire->id)
                ->where('role_id', Session::get('active_role_id'))
                ->exists();

            $questionnaire->status     = $hasAnswered ? 'Diisi' : 'Belum Diisi';
            $questionnaire->targetRole = collect($questionnaire->targets)->pluck('target_value')->implode(', ');
            $questionnaire->dueDate    = $questionnaire->end_date;

            if ($hasAnswered) {
                $completedQuestionnairesCount++;
            } else {
                $uncompletedQuestionnairesCount++;
            }

            return $questionnaire;
        });

        return Inertia::render('Dashboard/User', [
            'questionnaires'                 => $questionnaires,
            'completedQuestionnairesCount'   => $completedQuestionnairesCount,
            'uncompletedQuestionnairesCount' => $uncompletedQuestionnairesCount,
        ]);
    }
}
