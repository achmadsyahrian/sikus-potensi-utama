<?php

namespace App\Services\Sevima;

class ProgramStudyService extends BaseService
{
    public static function fetchProgramStudies(array $params = [])
    {
        $response = self::makeRequest("program-studi", $params);
        return $response->successful() ? $response->json() : null;
    }
}
