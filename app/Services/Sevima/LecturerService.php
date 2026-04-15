<?php
namespace App\Services\Sevima;

use App\Models\LecturerDetail;
use App\Models\User;

class LecturerService extends BaseService
{
    public static function fetchLecturers(array $params = [])
    {
        $response = self::makeRequest("dosen", $params);
        return $response->successful() ? $response->json('data') : null;
    }

    public static function fetchLecturerById(string $id, array $params = []): ?array
    {
        $response = self::makeRequest("dosen/{$id}", $params);
        return $response->successful() ? $response->json() : null;
    }

    public static function fetchLecturerSchedule(string $lecturerId, string $periodId, array $params = []): ?array
    {
        $response = self::makeRequest("dosen/{$lecturerId}/jadwal", array_merge(['f-id_periode' => $periodId], $params));
        return $response->successful() ? $response->json() : null;
    }

    public function syncLecturer(User $user, array $sevimaData): LecturerDetail
    {
        $sevimaId = data_get($sevimaData, 'id');
        $sevimaData         = $sevimaData['attributes'] ?? $sevimaData;
        $nidn               = data_get($sevimaData, 'nidn');
        $workUnit           = data_get($sevimaData, 'satuan_kerja') ?? null;
        $functionalPosition = data_get($sevimaData, 'jabatan_fungsional') ?? null;
        $nidn     = ! empty($nidn) ? $nidn : null;
        $workUnit = ! empty($workUnit) ? $workUnit : null;

        $lecturerDetail = LecturerDetail::updateOrCreate(
            ['user_id' => $user->id, 'nidn' => $nidn, 'sevima_id' => $sevimaId],
            [
                'work_unit'           => $workUnit,
                'functional_position' => $functionalPosition,
            ]
        );

        return $lecturerDetail;
    }
}
