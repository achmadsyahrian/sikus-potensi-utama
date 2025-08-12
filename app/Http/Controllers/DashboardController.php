<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\ProgramStudy;
use App\Models\Questionnaire;
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
        // TODO: Tambahkan logika untuk mengambil data ringkasan admin, seperti:
        // - Jumlah kuesioner aktif
        // - Jumlah responden
        // - Kuesioner terbaru yang diaktifkan
        $data = [
            'activeQuestionnairesCount' => 0,
            'totalResponsesCount' => 0,
        ];

        return Inertia::render('Dashboard/Admin', [
            'data' => $data,
            'activeRole' => $activeRole->name,
        ]);
    }

    protected function userIndex(Request $request, $activeRole)
    {
        $user = $request->user();
        $userId = $user->id;

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

        // Tandai status sudah/belum diisi
        $questionnaires = $questionnaires->map(function ($questionnaire) use ($userId) {
            $hasAnswered = DB::table('answers')
                ->where('user_id', $userId)
                ->where('questionnaire_id', $questionnaire->id)
                ->exists();

            $questionnaire->status = $hasAnswered ? 'Diisi' : 'Belum Diisi';
            $questionnaire->targetRole = collect($questionnaire->targets)->pluck('target_value')->implode(', ');
            $questionnaire->dueDate = $questionnaire->end_date;

            return $questionnaire;
        });

        return Inertia::render('Dashboard/User', [
            'questionnaires' => $questionnaires,
        ]);
    }
}
