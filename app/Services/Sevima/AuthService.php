<?php

namespace App\Services\Sevima;

class AuthService extends BaseService
{
    public static function attemptLogin(string $email, string $password): ?array
    {
        $response = self::makeRequest(
            'user/login',
            [
                'email' => $email,
                'password' => $password,
            ],
            'POST'
        );

        return $response->successful() ? $response->json() : null;
    }
}
