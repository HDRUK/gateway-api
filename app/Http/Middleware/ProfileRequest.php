<?php

namespace App\Http\Middleware;

use Config;

use SebastianBergmann\Timer\Timer;
use SebastianBergmann\Timer\ResourceUsageFormatter;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Config::get('profiling.profiler_active')) {
            // Create our profiler
            $timer = new Timer();
            $timer->start();

            // Process the request
            $response = $next($request);

            // Stop our profiler
            $duration = (new ResourceUsageFormatter())->resourceUsage($timer->stop());
            $parts = explode('\\', $request->route()->getAction()['controller']);
            $className = $parts[count($parts) - 1];

            $resourceUsed = [
                'explicitOperation' => $className,
                'operationResource' => $duration,
            ];

            $response->setData($response->getData(true) + [
                '_profiler' => $resourceUsed,
            ]);
            // Return response with profiling appended to payload
            return $response;
        }

        return $next($request);
    }
}
