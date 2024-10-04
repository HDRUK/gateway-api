<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{

    // 'CORS_ACCESS_CONTROL_ALLOW_ORIGIN' = https://web.dev.hdruk.cloud
    // 'RQUEST_INIT_URL' = 'https://rquest.test.healthdatagateway.org/bcrquest/'
    public function handle(Request $request, Closure $next)
    {
        $headers = [
            'Access-Control-Allow-Origin' => env('CORS_ACCESS_CONTROL_ALLOW_ORIGIN'),
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization',
        ];

        if ($request->getMethod() === 'OPTIONS') {
            return response('OK')->withHeaders($headers);
        }

        $response = $next($request);
        foreach ($headers as $key => $value) {
            $response->headers->add([$key => $value]);
        }
        return $response;
    }

    // 'CORS_ACCESS_CONTROL_ALLOW_ORIGIN' = https://web.dev.hdruk.cloud,https://rquest.dev.hdruk.cloud,https://rquest.test.healthdatagateway.org
    // 'RQUEST_INIT_URL' = 'https://rquest.test.healthdatagateway.org/bcrquest/'
    // public function handle(Request $request, Closure $next)
    // {
    //     $allowedOrigins = explode(',', env('CORS_ACCESS_CONTROL_ALLOW_ORIGIN'));

    //     $origin = $request->headers->get('Origin');

    //     if (in_array($origin, $allowedOrigins)) {
    //         $headers = [
    //             'Access-Control-Allow-Origin' => $origin,
    //             'Access-Control-Allow-Credentials' => 'true',
    //             'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
    //             'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type, Accept, Authorization',
    //         ];
    //     } else {
    //         $headers = [
    //             'Access-Control-Allow-Origin' => 'https://rquest.test.healthdatagateway.org',
    //             'Access-Control-Allow-Credentials' => 'true',
    //             'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
    //             'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type, Accept, Authorization',
    //         ];
    //     }

    //     if ($request->getMethod() === 'OPTIONS') {
    //         return response('OK')->withHeaders($headers);
    //     }

    //     $response = $next($request);
    //     foreach ($headers as $key => $value) {
    //         $response->headers->add([$key => $value]);
    //     }
    //     return $response;
    // }
}
