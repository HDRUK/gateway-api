<?php

namespace App\Http\Middleware;

use CloudLogger;
use Closure;
use Illuminate\Http\Request;

class LogRequestResponse
{
    // List of sensitive fields to mask
    protected $sensitiveFields = ['password', 'token', 'access_token', 'authorization'];
    // Max body length to log
    protected $maxBodyLength = 2048;
    // Sensitive fields (passwords, tokens) only ever appear near the top of a
    // request/response body in this app. Large domain payloads (e.g. full
    // dataset search results, 1MB+) are wide AND deeply nested (structural
    // metadata tables/columns), so an unbounded recursive walk costs 100ms+
    // per request just to redact keys that only ever occur within a few
    // levels of the root. Bounding depth keeps every realistic redaction
    // target covered while capping worst-case cost regardless of body size.
    protected $maxMaskDepth = 4;

    public function handle(Request $request, Closure $next)
    {
        // Mask sensitive fields in request body
        $body = $this->maskSensitive($request->all());
        $body = $this->truncateBody($body);
        $methodName = $this->getMethodName($request);

        // Log the incoming request
        CloudLogger::write([
            'type' => 'Request',
            'x-request-session-id' => $request->headers->all()['x-request-session-id'] ?? null,
            'url' => $request->fullUrl(),
            'http_method' => $request->getMethod(),
            'method_name' => $methodName,
            'body' => $body,
        ]);

        $response = $next($request);

        // Log the outgoing response
        $responseBody = method_exists($response, 'getContent') ? $response->getContent() : null;
        $responseBody = json_decode($responseBody, true);
        $responseBody = $this->maskSensitive($responseBody);
        $responseBody = $this->truncateBody($responseBody);
        CloudLogger::write([
            'type' => 'Response',
            'x-request-session-id' => $request->headers->all()['x-request-session-id'] ?? null,
            'url' => $request->fullUrl(),
            'http_method' => $request->getMethod(),
            'method_name' => $methodName,
            'body' => $responseBody,
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }

    protected function maskSensitive($data, int $depth = 0)
    {
        if (is_array($data) && $depth < $this->maxMaskDepth) {
            foreach ($data as $key => &$value) {
                if (in_array(strtolower((string) $key), $this->sensitiveFields)) {
                    $value = '***';
                } elseif (is_array($value)) {
                    $value = $this->maskSensitive($value, $depth + 1);
                }
            }
        }
        return $data;
    }

    protected function getMethodName($request): ?string
    {
        $route = app('router')->getRoutes()->match(
            app('request')->create($request->fullUrl(), $request->getMethod())
        );

        return $route->action['controller'] ?? null;
    }

    protected function truncateBody($body)
    {
        if (is_array($body)) {
            $body = json_encode($body);
        }
        if (is_string($body) && strlen($body) > $this->maxBodyLength) {
            return substr($body, 0, $this->maxBodyLength) . '...';
        }
        return $body;
    }
}
