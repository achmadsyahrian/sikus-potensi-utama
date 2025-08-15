<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Faculty;
use App\Models\ProgramStudy;
use App\Models\Questionnaire;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $activeRoleId = Session::get('active_role_id');
        $activeRole = $user->roles->find($activeRoleId);

        if (!$activeRole && $user->roles->count() > 0) {
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
        $totalQuestionnairesCount = Questionnaire::count();
        $activeQuestionnairesCount = Questionnaire::where('is_active', true)->count();
        $totalResponsesCount = DB::table('answers')->count();
        $totalProgramStudiesCount = ProgramStudy::count();
        $totalFacultiesCount = Faculty::count();
        $totalAcademicPeriodsCount = AcademicPeriod::count();
        $totalUsersCount = ($activeRole->slug === 'superadmin') ? User::count() : null;
        $totalLocalUsersCount = ($activeRole->slug === 'superadmin') ? User::where('auth_provider', 'local')->count() : null;
        $totalSevimaUsersCount = ($activeRole->slug === 'superadmin') ? User::where('auth_provider', 'sevima')->count() : null;

        $totalResponsesByFaculty = DB::table('answers')
            ->join('student_details', 'answers.user_id', '=', 'student_details.user_id')
            ->join('program_studies', 'student_details.study_program', '=', 'program_studies.program_study_code')
            ->join('faculties', 'program_studies.faculty_code', '=', 'faculties.faculty_code')
            ->select('faculties.name as faculty_name', DB::raw('count(answers.id) as total'))
            ->groupBy('faculties.name')
            ->get();
        
        $responsesByRoleAndMonth = DB::table('answers')
            ->join('roles', 'answers.role_id', '=', 'roles.id')
            ->select('roles.name as role_name', DB::raw('DATE_FORMAT(answers.created_at, "%Y-%m") as month'), DB::raw('count(answers.id) as total'))
            ->groupBy('role_name', 'month')
            ->orderBy('month')
            ->get();

        $monthlyResponses = [];
        $roles = Role::whereNotIn('slug', ['superadmin', 'admin'])->get()->pluck('name')->toArray();
        $months = $responsesByRoleAndMonth->pluck('month')->unique()->sort()->values()->toArray();

        foreach ($roles as $roleName) {
            $data = [];
            foreach ($months as $month) {
                $response = $responsesByRoleAndMonth->where('role_name', $roleName)->where('month', $month)->first();
                $data[] = $response ? $response->total : 0;
            }
            $monthlyResponses[] = [
                'name' => $roleName,
                'data' => $data,
            ];
        }

        $totalResponsesByProgramStudy = DB::table('answers')
            ->join('student_details', 'answers.user_id', '=', 'student_details.user_id')
            ->join('program_studies', 'student_details.study_program', '=', 'program_studies.program_study_code')
            ->select('program_studies.name as program_study_name', DB::raw('count(answers.id) as total'))
            ->groupBy('program_studies.name')
            ->get();

        $totalResponsesByRole = DB::table('answers')
            ->join('roles', 'answers.role_id', '=', 'roles.id')
            ->select('roles.name as role_name', DB::raw('count(answers.id) as total'))
            ->groupBy('roles.name')
            ->get();

        $latestQuestionnaires = Questionnaire::latest()->take(5)->get(['id', 'name', 'start_date', 'end_date']);

        return Inertia::render('Dashboard/Admin', [
            'activeRole' => $activeRole->name,
            'userName' => $request->user()->name,
            'activeRoleSlug' => $activeRole->slug,
            'totalQuestionnairesCount' => $totalQuestionnairesCount,
            'activeQuestionnairesCount' => $activeQuestionnairesCount,
            'totalResponsesCount' => $totalResponsesCount,
            'totalProgramStudiesCount' => $totalProgramStudiesCount,
            'totalFacultiesCount' => $totalFacultiesCount,
            'totalAcademicPeriodsCount' => $totalAcademicPeriodsCount,
            'totalUsersCount' => $totalUsersCount,
            'totalLocalUsersCount' => $totalLocalUsersCount,
            'totalSevimaUsersCount' => $totalSevimaUsersCount,
            'latestQuestionnaires' => $latestQuestionnaires,
            'totalResponsesByFaculty' => $totalResponsesByFaculty,
            'monthlyResponses' => $monthlyResponses,
            'monthlyLabels' => $months,
            'totalResponsesByProgramStudy' => $totalResponsesByProgramStudy,
            'totalResponsesByRole' => $totalResponsesByRole,
        ]);
    }

    // protected function userIndex(Request $request, $activeRole)
    // {
    //     $user = $request->user();
    //     $userId = $user->id;

    //     $targetIdentifiers = [
    //         ['target_type' => 'role', 'target_value' => $activeRole->name],
    //     ];

    //     if ($activeRole->name === 'Mahasiswa' && $user->studentDetails) {
    //         $studentDetails = $user->studentDetails;
    //         $programStudy = ProgramStudy::where('program_study_code', $studentDetails->program_study_code)->first();
    //         $faculty = Faculty::where('faculty_code', $programStudy->faculty_code)->first();

    //         if ($programStudy) {
    //             $targetIdentifiers[] = ['target_type' => 'program_study', 'target_value' => $programStudy->id];
    //         }
    //         if ($faculty) {
    //             $targetIdentifiers[] = ['target_type' => 'faculty', 'target_value' => $faculty->id];
    //         }
    //     }

    //     $questionnaires = Questionnaire::query()
    //         ->with(['targets'])
    //         ->where('is_active', true)
    //         ->where(function ($q) use ($targetIdentifiers, $activeRole) {

    //             if ($activeRole->name !== 'Mahasiswa') {
    //                 $q->whereHas('targets', function ($subQuery) use ($activeRole) {
    //                     $subQuery->where('target_type', 'role')
    //                         ->where('target_value', $activeRole->name);
    //                 });
    //             } else {
    //                 $q->orWhereHas('targets', function ($subQuery) {
    //                     $subQuery->whereIn('target_type', ['university', 'all']);
    //                 });

    //                 $facultyMatch = collect($targetIdentifiers)
    //                     ->where('target_type', 'faculty')
    //                     ->pluck('target_value')
    //                     ->toArray();

    //                 $prodiMatch = collect($targetIdentifiers)
    //                     ->where('target_type', 'program_study')
    //                     ->pluck('target_value')
    //                     ->toArray();

    //                 $q->orWhere(function ($subQ) use ($facultyMatch, $prodiMatch) {
    //                     $subQ->where(function ($innerQ) use ($facultyMatch, $prodiMatch) {
    //                         if (!empty($facultyMatch)) {
    //                             $innerQ->orWhereHas('targets', function ($targetQ) use ($facultyMatch) {
    //                                 $targetQ->where('target_type', 'faculty')
    //                                     ->whereIn('target_value', $facultyMatch);
    //                             });
    //                         }
    //                         if (!empty($prodiMatch)) {
    //                             $innerQ->orWhereHas('targets', function ($targetQ) use ($prodiMatch) {
    //                                 $targetQ->where('target_type', 'program_study')
    //                                     ->whereIn('target_value', $prodiMatch);
    //                             });
    //                         }
    //                     });
    //                 });
    //             }
    //         })
    //         ->get();

    //     $questionnaires = $questionnaires->map(function ($questionnaire) use ($userId) {
    //         $hasAnswered = DB::table('answers')
    //             ->where('user_id', $userId)
    //             ->where('questionnaire_id', $questionnaire->id)
    //             ->where('role_id', Session::get('active_role_id'))
    //             ->exists();

    //         $questionnaire->status = $hasAnswered ? 'Diisi' : 'Belum Diisi';
    //         $questionnaire->targetRole = collect($questionnaire->targets)->pluck('target_value')->implode(', ');
    //         $questionnaire->dueDate = $questionnaire->end_date;

    //         return $questionnaire;
    //     });

    //     return Inertia::render('Dashboard/User', [
    //         'questionnaires' => $questionnaires,
    //     ]);
    // }

    protected function userIndex(Request $request, $activeRole)
    {
        $user = $request->user();
        $userId = $user->id;

        $targetIdentifiers = [
            ['target_type' => 'role', 'target_value' => $activeRole->name],
        ];

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

                if ($activeRole->name !== 'Mahasiswa') {
                    $q->whereHas('targets', function ($subQuery) use ($activeRole) {
                        $subQuery->where('target_type', 'role')
                            ->where('target_value', $activeRole->name);
                    });
                } else {
                    $q->orWhereHas('targets', function ($subQuery) {
                        $subQuery->whereIn('target_type', ['university', 'all']);
                    });

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

        $completedQuestionnairesCount = 0;
        $uncompletedQuestionnairesCount = 0;

        $questionnaires = $questionnaires->map(function ($questionnaire) use ($userId, &$completedQuestionnairesCount, &$uncompletedQuestionnairesCount) {
            $hasAnswered = DB::table('answers')
                ->where('user_id', $userId)
                ->where('questionnaire_id', $questionnaire->id)
                ->where('role_id', Session::get('active_role_id'))
                ->exists();

            $questionnaire->status = $hasAnswered ? 'Diisi' : 'Belum Diisi';
            $questionnaire->targetRole = collect($questionnaire->targets)->pluck('target_value')->implode(', ');
            $questionnaire->dueDate = $questionnaire->end_date;

            if ($hasAnswered) {
                $completedQuestionnairesCount++;
            } else {
                $uncompletedQuestionnairesCount++;
            }

            return $questionnaire;
        });

        return Inertia::render('Dashboard/User', [
            'questionnaires' => $questionnaires,
            'completedQuestionnairesCount' => $completedQuestionnairesCount,
            'uncompletedQuestionnairesCount' => $uncompletedQuestionnairesCount,
        ]);
    }
}
