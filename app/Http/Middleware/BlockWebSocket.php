<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockWebSocket
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $upgrade    = strtolower((string) $request->header('Upgrade'));
        $connection = strtolower((string) $request->header('Connection'));

        if ($upgrade === 'websocket' || str_contains($connection, 'upgrade')) {
            abort(403, 'WebSocket not supported.');
        }

        if ($request->hasHeader('Sec-WebSocket-Key')
            || $request->hasHeader('Sec-WebSocket-Version')
            || $request->hasHeader('Sec-WebSocket-Extensions')
            || $request->hasHeader('Sec-WebSocket-Protocol')) {
            abort(403, 'WebSocket not supported.');
        }

        return $next($request);
    }
}
