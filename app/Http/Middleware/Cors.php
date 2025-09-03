<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        // Exact Origin from the browser (includes scheme + host + port)
        $origin = $request->headers->get('Origin');

        // Prefer config() over env() at runtime (env() can be cached out)
        $allowedOrigins = array_filter([
            config('cors.allowed_origins.0') ?? env('CORS_ACCESS_CONTROL_ALLOW_ORIGIN'),
            config('app.gateway_url') ?? env('GATEWAY_URL'),
            // add more if needed
        ]);

        $headers = [
            // If you use cookies across origins, keep true
            'Access-Control-Allow-Credentials' => 'true',
            // Default allow list; we’ll narrow to the requested method below if present
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            // Will be replaced with requested headers if provided
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept, Origin, x-request-session-id',
            // Help caches do the right thing
            'Vary' => 'Origin',
        ];

        // Only reflect an allowed origin
        if ($origin && in_array($origin, $allowedOrigins, true)) {
            $headers['Access-Control-Allow-Origin'] = $origin;
        }

        // If this is a preflight request, echo back what the browser asked for
        if ($request->getMethod() === 'OPTIONS') {
            $reqMethod  = $request->headers->get('Access-Control-Request-Method');
            $reqHeaders = $request->headers->get('Access-Control-Request-Headers');

            if ($reqMethod) {
                // Ensure OPTIONS is included
                $headers['Access-Control-Allow-Methods'] = $reqMethod . ', OPTIONS';
            }
            if ($reqHeaders) {
                // Must be the exact comma-separated string the browser sent
                $headers['Access-Control-Allow-Headers'] = $reqHeaders;
            }

            // No body needed for preflight
            return response('', 204)->withHeaders($headers);
        }

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        foreach ($headers as $k => $v) {
            $response->headers->set($k, $v, true);
        }

        return $response;
    }
}
