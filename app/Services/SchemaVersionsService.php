<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SchemaVersionsService
{
    public static function getSchemaVersions()
    {
        $apiUrl = env("TRASER_SERVICE_URL") . '/latest';

        $response = Http::get($apiUrl);

        return $response->successful() ? $response->json() : null;
    }
}
