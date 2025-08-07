<?php

namespace App\Services\Sevima;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaseService
{
    protected static array $config;

    protected static function init()
    {
        if (!isset(self::$config)) {
            self::$config = config('services.sevima');
        }
    }

    protected static function headers(): array
    {
        self::init();

        return [
            'X-App-Key'    => self::$config['app_key'],
            'X-Secret-Key' => self::$config['secret_key'],
        ];
    }

    protected static function baseUrl(): string
    {
        self::init();
        return rtrim(self::$config['base_url'], '/');
    }

    protected static function makeRequest(string $uri, array $params = [], string $method = 'GET')
    {
        $http = Http::withHeaders(self::headers())
                    ->timeout(30);

        $url = self::baseUrl() . '/' . ltrim($uri, '/');

        try {
            $response = match (strtoupper($method)) {
                'POST' => $http->post($url, $params),
                'PUT' => $http->put($url, $params),
                'PATCH' => $http->patch($url, $params),
                'DELETE' => $http->delete($url, $params),
                default => $http->get($url, $params),
            };

            $response->throw();

            return $response;

        } catch (\Throwable $e) {
            Log::error('[BaseService Log] Exception during Sevima API call:', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url_called' => $url,
                'params_sent' => $params,
            ]);
            throw $e;
        }
    }


    protected static function cachedRequest(
        string $cacheKey,
        string $uri,
        array $params = [],
        int $durationHours = 3,
        ?string $pluck = null
    ) {
        return Cache::remember($cacheKey, now()->addHours($durationHours), function () use ($uri, $params, $pluck) {
            $response = self::makeRequest($uri, $params);

            if (! $response->successful()) {
                return null;
            }

            return $pluck ? $response->json($pluck) : $response->json();
        });

        // Cara penggunaan:
        // return self::cachedRequest("sevima_lecturer_{$id}", "dosen/{$id}", $params);
    }

    protected static function cacheKey(string $prefix, array $params = []): string
    {
        if (empty($params)) {
            return $prefix;
        }

        return $prefix . '_' . md5(http_build_query($params));

        // Cara penggunaan:
        // $key = self::cacheKey('sevima_lecturers', $params);
    }
}
