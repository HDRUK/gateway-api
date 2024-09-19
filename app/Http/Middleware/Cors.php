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

        // if ($request->getMethod() === 'OPTIONS') {
        //     return response('OK')->withHeaders($headers);
        // }

        // $response = $next($request);
        // foreach ($headers as $key => $value) {
        //     $response->headers->add([$key => $value]);
        // }

        // return $response;

        return $next($request)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Authorization,Accept,Origin,DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Content-Range,Range'); 
    }
}
