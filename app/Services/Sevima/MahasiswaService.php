<?php

namespace App\Services\Sevima;

use App\Models\Student;
use App\Models\StudentDetail;
use App\Models\User;
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
}
