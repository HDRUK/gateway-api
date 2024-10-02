<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        // $headers = [
        //     'Access-Control-Allow-Origin' => env('CORS_ACCESS_CONTROL_ALLOW_ORIGIN'),
        //     'Access-Control-Allow-Credentials' => 'true',
        //     'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        //     'Access-Control-Allow-Headers' => 'Content-Type, Origin, Authorization',
        // ];

        // $allowedOrigins = explode(',', env('CORS_ACCESS_CONTROL_ALLOW_ORIGINS'));
        $allowedOrigins = explode(',', 'https://web.dev.hdruk.cloud,https://rquest.dev.hdruk.cloud,https://rquest.test.healthdatagateway.org');

        $origin = $request->headers->get('Origin');
        // $origin = $request->headers->get('X-Forwarded-Host');
        \Log::info('$origin :: ' . json_encode($origin));
        \Log::info('Cors :: ' . json_encode($request));
        \Log::info('Cors 2 :: ' . json_encode($request->headers->get('X-Forwarded-Host')));

        if (in_array($origin, $allowedOrigins)) {
            $headers = [
                'Access-Control-Allow-Origin' => 'https://web.dev.hdruk.cloud',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type, Accept, Authorization',
            ];
            \Log::info('Cors A :: ' . json_encode($headers));
        } else {
            $headers = [
                'Access-Control-Allow-Origin' => 'https://web.dev.hdruk.cloud',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type, Accept, Authorization',
            ];
            \Log::info('Cors B :: ' . json_encode($headers));
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
