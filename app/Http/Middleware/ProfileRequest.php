<?php

namespace App\Http\Middleware;

use Config;

use SebastianBergmann\Timer\Timer;
use SebastianBergmann\Timer\ResourceUsageFormatter;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileRequest
{
    public function handle(Request $request, Closure $next): JsonResponse|StreamedResponse|RedirectResponse|Response|BinaryFileResponse
    {
        if (Config::get('profiling.profiler_active')) {
            // Create our profiler
            $timer = new Timer();
            $timer->start();

            // Process the request
            $response = $next($request);

            if ($response instanceof JsonResponse) {
                // Stop our profiler
                $duration = (new ResourceUsageFormatter())->resourceUsage($timer->stop());
                dd($request->route()->getAction());
                $parts = explode('\\', $request->route()->getAction()['controller']);
                $className = $parts[count($parts) - 1];

                $resourceUsed = [
                    'explicitOperation' => $className,
                    'operationResource' => $duration,
                ];

                $response->setData($response->getData(true) + [
                    '_profiler' => $resourceUsed,
                ]);
            } else {
                return $next($request);
            }

            // Return response with profiling appended to payload
            return $response;
        }

        return $next($request);
    }
}
