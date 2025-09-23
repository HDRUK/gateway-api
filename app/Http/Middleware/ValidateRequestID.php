<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\UnauthorizedException;

class ValidateRequestID
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = trim($request->header('x-request-session-id'));
        // Header can have web: appended to it by front end
        if ($header !== null && !preg_match('/^[a-zA-Z0-9: -]+$/', $header)) {
            throw new UnauthorizedException('The credentials provided are invalid');

        }
        return $next($request);
    }
}
