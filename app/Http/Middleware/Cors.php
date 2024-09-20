<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = [
            env('CORS_ACCESS_CONTROL_ALLOW_ORIGIN'),
            env('RQUEST_INIT_URL'),
        ];
        $origin = $request->headers->get('Origin');

        \Log::info('Cors - origin :: ' . $request->headers->get('Origin'));

        $headers = [
            // 'Access-Control-Allow-Origin' => env('CORS_ACCESS_CONTROL_ALLOW_ORIGIN'),
            // 'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization',
        ];

        // If Origin is null, decide how to handle it
        if ($origin === 'null') {
            // Option 1: Allow 'null' Origin (not generally recommended)
            $headers['Access-Control-Allow-Origin'] = 'null';
        } elseif (in_array($origin, $allowedOrigins)) {
            // Allow specific origins
            $headers['Access-Control-Allow-Origin'] = $origin;
        } else {
            // Optionally, deny the request
            return response('Forbidden', 403)->withHeaders($headers);
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
