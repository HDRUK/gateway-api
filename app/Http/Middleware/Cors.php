<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        header('Access-Control-Allow-Origin: ' . env('CORS_ACCESS_CONTROL_ALLOW_ORIGIN'));
        header('Access-Control-Allow-Credentials: true');

        $headers = [
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization',
            'Access-Control-Expose-Headers' => 'Content-Disposition',
        ];

        if ($request->getMethod() === 'OPTIONS') {
            return response('OK')->withHeaders($headers);
        }

        $response = $next($request);
        foreach ($headers as $key => $value)
            $response->headers->add([$key => $value]);
        return $response;
    }
}
