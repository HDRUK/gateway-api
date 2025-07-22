<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SunsetHeader
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $deprecationDate = 'Thurs, 22 Jan 2026 23:59:59 GMT';

        $response->headers->set('Deprecation', 'true');
        $response->headers->set('Sunset', $deprecationDate);

        return $response;
    }
}
