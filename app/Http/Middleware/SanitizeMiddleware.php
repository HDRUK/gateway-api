<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$input) {
            // $input = strip_tags($input);
            $input = is_string($input) ? htmlentities(trim($input), ENT_QUOTES, "UTF-8") : $input;
        });
        $request->merge($input);

        return $next($request);
    }
}
