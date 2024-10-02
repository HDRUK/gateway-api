<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $headers = [
            'Access-Control-Allow-Origin' => env('CORS_ACCESS_CONTROL_ALLOW_ORIGIN'),
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization',
        ];

        // $allowedOrigins = explode(',', env('CORS_ACCESS_CONTROL_ALLOW_ORIGINS'));
        // $allowedOrigins = explode(',', 'https://web.dev.hdruk.cloud,https://rquest.dev.hdruk.cloud,https://rquest.test.healthdatagateway.org');

        // $origin = $request->headers->get('Origin');

        // if (in_array($origin, $allowedOrigins)) {
        //     $headers = [
        //         'Access-Control-Allow-Origin' => $origin,
        //         'Access-Control-Allow-Credentials' => 'true',
        //         'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        //         'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization',
        //     ];
        // } else {
        //     // Optionally handle disallowed origins
        //     $headers = [
        //         'Access-Control-Allow-Origin' => 'null',
        //         'Access-Control-Allow-Credentials' => 'true',
        //         'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        //         'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization',
        //     ];
        // }

        if ($request->getMethod() === 'OPTIONS') {
            return response('OK')->withHeaders($headers);
        }

        $response = $next($request);
        foreach ($headers as $key => $value) {
            $response->headers->add([$key => $value]);
        }
        return $response;
    }
}
