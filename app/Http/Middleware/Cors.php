<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->headers->get('Origin');
        \Log::info('Cors $origin :: ' . json_encode($origin));

        $headers = [
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization',
        ];

        if ($origin) {
            $headers['Access-Control-Allow-Origin'] = $origin;
            $headers['Access-Control-Allow-Credentials'] = 'true';
            $headers['Vary'] = 'Origin';
        }

        if ($request->getMethod() === 'OPTIONS') {
            return response('OK', 200, $headers);
        }

        $response = $next($request);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        \Log::info('Cors $response :: ' . json_encode($response));
        return $response;
    }
}
