<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->headers->get('origin');

        $list = config('CORS_ACCESS_CONTROL_ALLOW_ORIGIN', '');
        $allowedOrigins = array_filter(array_map('trim', explode(',', $list)));

        $dta = trim((string) config('DTA_URL', ''));
        if ($dta !== '') {
            $allowedOrigins[] = $dta;
        }

        $headers = [
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization, x-request-session-id',
            'Vary' => 'Origin, Access-Control-Request-Method, Access-Control-Request-Headers',
        ];

        if ($origin && (in_array($origin, $allowedOrigins, true))) {
            $headers['Access-Control-Allow-Origin'] = $origin;
        }

        if ($request->getMethod() === 'OPTIONS') {
            return response('OK')->withHeaders($headers);
        }

        $response = $next($request);
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value, false);
        }

        return $response;
    }
}
