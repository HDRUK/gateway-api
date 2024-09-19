<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $urls = "https://web.dev.hdruk.cloud,https://rquest.dev.hdruk.cloud/rquest/,https://rquest.test.healthdatagateway.org/bcrquest/";
        // $allowedOrigins = explode(',', env('CORS_ACCESS_CONTROL_ALLOW_ORIGINS'));
        $allowedOrigins = explode(',', $urls);

        $origin = $request->headers->get('Origin');

        \Log::info('Cors - $origin :: ' . json_encode($origin));
        \Log::info('Cors - request headers:', $request->headers->all());

        $headers = [
            // 'Access-Control-Allow-Origin' => env('CORS_ACCESS_CONTROL_ALLOW_ORIGIN'),
            'Access-Control-Allow-Origin' => '*',
            // 'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization',
        ];

        // if ($origin === 'null') {
        //     // Option 1: Allow 'null' Origin (not generally recommended)
        //     $headers['Access-Control-Allow-Origin'] = 'null';
        // } elseif (in_array($origin, $allowedOrigins)) {
        //     // Allow specific origins
        //     $headers['Access-Control-Allow-Origin'] = $origin;
        // } else {
        //     // Optionally, deny the request
        //     return response('Forbidden', 403)->withHeaders($headers);
        // }

        if ($request->getMethod() === 'OPTIONS') {
            return response('OK')->withHeaders($headers);
        }

        $response = $next($request);
        foreach ($headers as $key => $value) {
            $response->headers->add([$key => $value]);
        }

        \Log::info('Cors - $response :: ' . json_encode($response));
        return $response;
    }
}
