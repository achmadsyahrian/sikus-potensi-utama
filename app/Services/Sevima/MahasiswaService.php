<?php

namespace App\Services\Sevima;

use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MahasiswaService extends BaseService
{
    public static function fetchStudents(array $params = [])
    {
        $response = self::makeRequest("mahasiswa", $params);
        return $response->successful() ? $response->json('data') : null;
    }

    public static function fetchStudentsByNim(string $nim, array $params = []): ?array
    {
        $response = self::makeRequest("mahasiswa/{$nim}", $params);
        return $response->successful() ? $response->json() : null;
    }

    public function syncStudent(User $user, array $sevimaData): StudentDetail
    {
        $sevimaData = $sevimaData['attributes'] ?? $sevimaData;
        $nim = data_get($sevimaData, 'nim');
        $studyProgram = data_get($sevimaData, 'program_studi') ?? null;
        $studyProgramCode = data_get($sevimaData, 'id_program_studi') ?? null;
        $studyProgramCode = data_get($sevimaData, 'id_program_studi') ?? null;
        $domicileAddress = data_get($sevimaData, 'alamat_domisili') ?? null;
        $phoneNumber = data_get($sevimaData, 'hp') ?? null;


        $nim = !empty($nim) ? $nim : null;
        $studyProgram = !empty($studyProgram) ? $studyProgram : null;

        $studentDetail = StudentDetail::updateOrCreate(
            ['user_id' => $user->id, 'nim' => $nim],
            [
                'study_program' => $studyProgram,
                'domicile_address' => Str::title($domicileAddress),
                'phone_number' => $phoneNumber,
                'program_study_code' => $studyProgramCode,
            ]
        );

        return $studentDetail;
    }

    public function getAllStudents(int $page = 1): ?array
    {
        try {
            $params = ['page' => $page, 'o-id' => 'asc'];

            // Di sini kita bisa gunakan Http facade langsung dengan fitur retry
            $response = Http::withHeaders(self::headers())
                ->baseUrl(self::baseUrl())
                ->timeout(30)
                ->retry(3, 60000, function ($exception, $request) {
                    return $exception->response->status() === 429;
                })
                ->get("mahasiswa", $params);

            $response->throw();

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Error fetching students from Sevima API: ' . $e->getMessage());
            return null;
        }
    }


    /**
     * Menerima satu data mahasiswa dari API, lalu melakukan sinkronisasi
     * ke dalam tabel `users` dan `student_details`.
     */
    public function syncUserAndStudentDetail(array $sevimaStudent, \DateTime $syncTime): void
    {
        $attributes = $sevimaStudent['attributes'] ?? $sevimaStudent;

        // FILTER #1: Berdasarkan Tahun Periode
        $idPeriode = data_get($attributes, 'id_periode');
        if ($idPeriode && strlen($idPeriode) >= 4) {
            $tahunPeriode = (int) substr($idPeriode, 0, 4);
            if ($tahunPeriode < 2017) {
                return;
            }
        } else {
            return;
        }

        $nim = data_get($attributes, 'nim');

        // =================================================================
        // === FILTER BARU: JANGAN PROSES NIM YANG BERAKHIRAN 'X' ===
        // =================================================================
        // Cek jika NIM tidak kosong dan berakhiran 'X' (case-insensitive)
        if ($nim && str_ends_with(strtoupper($nim), 'X')) {
            return; // SKIP
        }
        // =================================================================

        $emailFromApi = data_get($attributes, 'email');
        $name = data_get($attributes, 'nama');

        // Validasi utama NIM tidak boleh kosong
        if (empty($nim)) {
            Log::warning("Skipping student due to missing NIM.", ['data' => $attributes]);
            return;
        }

        $email = !empty($emailFromApi) ? $emailFromApi : "{$nim}@potensi-utama.ac.id";

        DB::transaction(function () use ($nim, $email, $name, $attributes, $syncTime) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => Str::title($name),
                    'password' => null,
                    'sevima_user_id' => $nim,
                    'auth_provider' => 'sevima',
                ]
            );

            $mahasiswaRole = Role::firstWhere('slug', 'mahasiswa');
            if ($mahasiswaRole) {
                $user->roles()->syncWithoutDetaching([$mahasiswaRole->id]);
            }

            StudentDetail::updateOrCreate(
                ['nim' => $nim],
                [
                    'user_id' => $user->id,
                    'study_program' => data_get($attributes, 'program_studi'),
                    'program_study_code' => data_get($attributes, 'id_program_studi'),
                    'domicile_address' => Str::title(data_get($attributes, 'alamat_domisili')),
                    'phone_number' => data_get($attributes, 'hp'),
                    'synced_at' => $syncTime,
                ]
            );
        });
    }
}
