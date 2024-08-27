<?php

namespace App\Http\Middleware;

use Config;

use Closure;
use Illuminate\Http\Request;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use SebastianBergmann\Timer\Timer;
use Illuminate\Http\RedirectResponse;
use SebastianBergmann\Timer\ResourceUsageFormatter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfileRequest
{
    public function handle(Request $request, Closure $next): JsonResponse|StreamedResponse|RedirectResponse|Response|BinaryFileResponse
    {
        // "App\\Http\\Middleware\\ProfileRequest::handle(): Return value must be of type 
        // Illuminate\\Http\\JsonResponse|
        // Symfony\\Component\\HttpFoundation\\StreamedResponse|
        // Illuminate\\Http\\RedirectResponse|
        // Illuminate\\Http\\Response|Symfony\\Component\\HttpFoundation\\BinaryFileResponse, Symfony\\Component\\HttpFoundation\\Response returned"
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
