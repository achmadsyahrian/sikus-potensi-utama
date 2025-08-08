<?php

namespace App\Services\Sevima;

class FacultyService extends BaseService
{
    public static function fetchFaculties(array $params = [])
    {
        $response = self::makeRequest("fakultas", $params);
        return $response->successful() ? $response->json() : null;
    }
}
