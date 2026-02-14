<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Faculty;
use App\Models\ProgramStudy;
use App\Models\Questionnaire;
use App\Models\Role;
use App\Models\User;
use App\Models\SatisfactionCriterion;
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

        $totalResponsesCount = DB::table('answers')->count();

        $totalProgramStudiesCount  = ProgramStudy::count();
        $totalFacultiesCount       = Faculty::count();
        $totalAcademicPeriodsCount = AcademicPeriod::count();
        $totalUsersCount           = ($activeRole->slug === 'superadmin') ? User::count() : null;
        $totalLocalUsersCount      = ($activeRole->slug === 'superadmin') ? User::where('auth_provider', 'local')->count() : null;
        $totalSevimaUsersCount     = ($activeRole->slug === 'superadmin') ? User::where('auth_provider', 'sevima')->count() : null;

        $totalResponsesByFaculty = DB::table('answers')
            ->join('student_details', 'answers.user_id', '=', 'student_details.user_id')
            ->join('program_studies', 'student_details.program_study_code', '=', 'program_studies.program_study_code')
            ->join('faculties', 'program_studies.faculty_code', '=', 'faculties.faculty_code')
            ->select('faculties.name as faculty_name', DB::raw('count(answers.id) as total'))
            ->groupBy('faculties.name')
            ->get();

        $responsesByRoleAndMonth = DB::table('answers')
            ->leftJoin('roles', 'answers.role_id', '=', 'roles.id')
            ->select(DB::raw('COALESCE(roles.name, "Eksternal") as role_name'), DB::raw('DATE_FORMAT(answers.created_at, "%Y-%m") as month'), DB::raw('count(answers.id) as total'))
            ->groupBy('role_name', 'month')
            ->orderBy('month')
            ->get();

        $roles   = Role::whereNotIn('slug', ['superadmin', 'admin'])->get()->pluck('name')->toArray();
        $roles[] = 'Eksternal';
        $months  = $responsesByRoleAndMonth->pluck('month')->unique()->sort()->values()->toArray();

        $monthlyResponses = [];
        foreach ($roles as $roleName) {
            $data = [];
            foreach ($months as $month) {
                $response = $responsesByRoleAndMonth->where('role_name', $roleName)->where('month', $month)->first();
                $data[]   = $response ? $response->total : 0;
            }
            $monthlyResponses[] = [
                'name' => $roleName,
                'data' => $data,
            ];
        }

        $totalResponsesByProgramStudy = DB::table('answers')
            ->join('student_details', 'answers.user_id', '=', 'student_details.user_id')
            ->join('program_studies', 'student_details.program_study_code', '=', 'program_studies.program_study_code')
            ->select('program_studies.name as program_study_name', DB::raw('count(answers.id) as total'))
            ->groupBy('program_studies.name')
            ->get();

        $totalResponsesByRole = DB::table('answers')
            ->leftJoin('roles', 'answers.role_id', '=', 'roles.id')
            ->select(DB::raw('COALESCE(roles.name, "Eksternal") as role_name'), DB::raw('count(answers.id) as total'))
            ->groupBy('role_name')
            ->get();

        $latestQuestionnaires = Questionnaire::latest()->take(5)->get(['id', 'name', 'start_date', 'end_date', 'academic_period_id']);

        $globalSatisfactionTrend = AcademicPeriod::orderBy('name', 'asc')
            ->get()
            ->map(function ($period) {
                // Ambil semua kuesioner di periode ini
                $questionnaires = Questionnaire::where('academic_period_id', $period->id)->get();

                $periodTotalScore       = 0; // Total poin yang didapat user
                $periodMaxPossibleScore = 0; // Total poin maksimal (jika semua jawab sempurna)

                foreach ($questionnaires as $q) {
                    // 1. Cari Skala Max kuesioner ini (misal 4 atau 5)
                    $maxScale = DB::table('question_options')
                        ->where('questionnaire_id', $q->id)
                        ->max('option_value');

                    // Default 4 jika belum disetting opsinya
                    $maxScale = $maxScale ?: 4;

                    // 2. Ambil Total Nilai & Jumlah Jawaban
                    $stats = DB::table('answers')
                        ->join('questions', 'answers.question_id', '=', 'questions.id')
                        ->where('answers.questionnaire_id', $q->id)
                        ->where('questions.question_type', 'multiple_choice') // Hanya Pilihan Ganda
                        ->selectRaw('sum(answers.answer_value) as sum_val, count(answers.id) as count_val')
                        ->first();

                    if ($stats->count_val > 0) {
                        // Akumulasi ke level Periode
                        $periodTotalScore       += $stats->sum_val;
                        $periodMaxPossibleScore += ($stats->count_val * $maxScale);
                    }
                }

                // 3. Hitung Persentase Akhir per Periode
                // Rumus: (Total Skor / Total Max) * 100
                $finalPercentage = 0;
                if ($periodMaxPossibleScore > 0) {
                    $finalPercentage = ($periodTotalScore / $periodMaxPossibleScore) * 100;
                }

                return [
                    'period_name'   => $period->name,
                    'average_score' => round($finalPercentage, 1), // Langsung Persen (misal: 76.4)
                ];
            })
            ->filter(function ($item) {
                // Hanya tampilkan periode yang ada datanya (skor > 0)
                return $item['average_score'] > 0;
            })
            ->values();

        $criteria = SatisfactionCriterion::orderBy('min_value', 'asc')->get();

        $topQuestionnairesStats = Questionnaire::withCount('answers')
            ->with(['academicPeriod'])
            ->where('is_active', true)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($q) use ($criteria) {
                // 1. Tetap cari Max Scale dinamis kuesioner ini untuk hitung persen
                $maxScale = DB::table('question_options')
                    ->where('questionnaire_id', $q->id)
                    ->max('option_value') ?: 1;

                // 2. Hitung statistik jawaban
                $stats = DB::table('answers')
                    ->join('questions', 'answers.question_id', '=', 'questions.id')
                    ->where('answers.questionnaire_id', $q->id)
                    ->where('questions.question_type', 'multiple_choice')
                    ->selectRaw('sum(answers.answer_value) as total_score, count(answers.id) as total_answers')
                    ->first();

                // 3. Hitung Persentase (Weighted)
                $percentage = 0;
                if ($stats->total_answers > 0) {
                    $percentage = ($stats->total_score / ($stats->total_answers * $maxScale)) * 100;
                }
                $percentage = round($percentage, 1);

                // 4. COCOKKAN DENGAN TABEL KRITERIA
                $matchedCriterion = $criteria->first(function ($c) use ($percentage) {
                    return $percentage >= $c->min_value && $percentage <= $c->max_value;
                });

                return [
                    'id'              => $q->id,
                    'name'            => $q->name,
                    'period'          => $q->academicPeriod->name,
                    'total_responses' => $q->answers_count,
                    'percentage'      => $percentage,
                    // Kirim info kriteria dari database
                    'label'           => $matchedCriterion ? $matchedCriterion->label : 'N/A',
                    'color'           => $matchedCriterion ? $matchedCriterion->color : '#6c757d',
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
