<?php

namespace App\Services\Sevima;

class AcademicPeriodService extends BaseService
{
    public static function fetchAcademicPeriods(array $params = [])
    {
        $response = self::makeRequest("periode", $params);
        return $response->successful() ? $response->json() : null;
    }
}
