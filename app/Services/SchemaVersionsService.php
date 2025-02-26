<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SchemaVersionsService
{
    public static function getSchemaVersions()
    {
        $apiUrl = env("TRASER_SERVICE_URL") . '/latest';

        return Cache::remember('schema_versions', 3600, function () use ($apiUrl) {
            $response = Http::get($apiUrl);

            return $response->successful() ? $response->json() : null;
        });
    }
}
