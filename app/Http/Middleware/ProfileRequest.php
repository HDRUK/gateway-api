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
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ProfileRequest
{
    /**
     * profile request middleware
     *
     * @param Request $request
     * @param Closure $next
     * @return JsonResponse|StreamedResponse|RedirectResponse|Response|BinaryFileResponse|SymfonyResponse
     */
    public function handle(Request $request, Closure $next): JsonResponse|StreamedResponse|RedirectResponse|Response|BinaryFileResponse|SymfonyResponse
    {
        // if (Config::get('profiling.profiler_active')) {
        //     // Create our profiler
        //     $timer = new Timer();
        //     $timer->start();

        //     // Process the request
        //     $response = $next($request);

        //     if ($response instanceof JsonResponse) {
        //         // Stop our profiler
        //         $duration = (new ResourceUsageFormatter())->resourceUsage($timer->stop());
        //         $parts = explode('\\', $request->route()->getAction()['controller']);
        //         $className = $parts[count($parts) - 1];

        //         $resourceUsed = [
        //             'explicitOperation' => $className,
        //             'operationResource' => $duration,
        //         ];

        //         $response->setData($response->getData(true) + [
        //             '_profiler' => $resourceUsed,
        //         ]);
        //     } else {
        //         return $next($request);
        //     }

        //     // Return response with profiling appended to payload
        //     return $response;
        // }

        return $next($request);
    }
}
