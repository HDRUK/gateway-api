<?php

namespace App\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SunsetHeader
{
    public function handle(Request $request, Closure $next, string $deprecatedOn, string $sunsetOn, string ...$successor): Response
    {
        $response = $next($request);

        $response->headers->set('Deprecation', '@' . CarbonImmutable::parse($deprecatedOn, 'UTC')->startOfDay()->timestamp);
        $response->headers->set('Sunset', CarbonImmutable::parse($sunsetOn, 'UTC')->endOfDay()->toRfc7231String());

        if ($successor !== []) {
            $response->headers->set('Link', "<{$successor[0]}>; rel=\"successor-version\"");
        }

        return $response;
    }
}
