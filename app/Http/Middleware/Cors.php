<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->headers->get('origin');

        $allowedOrigins = [
            env('CORS_ACCESS_CONTROL_ALLOW_ORIGIN'),
            env('DTA_URL')
        ];

        $headers = [
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization, x-request-session-id',
        ];

        if (in_array($origin, $allowedOrigins)) {
            $headers['Access-Control-Allow-Origin'] = $origin;
        }

        // let /oauth/authorize in
        if ($request->is('oauth/authorize')) {
            $headers['Access-Control-Allow-Origin'] = 'https://rquest.dev.hdruk.cloud';
        }

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
