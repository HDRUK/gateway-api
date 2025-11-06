<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log; 
use App\Http\Traits\CustomIdTokenTrait;
use Symfony\Component\HttpFoundation\Response;
// use DB;
class AppendTokenResponse
{
    use CustomIdTokenTrait;

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response|JsonResponse
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        // Log::info('AppendTokenResponse::handle called', [
        // 'route' => $request->route()?->getName(),
        // 'controller' => $request->route()?->getActionName(),
        // 'request_body' => $request->all(),
        // 'query' => $request->query(),
        // 'headers' => $request->headers->all(),
        // ]);

    //    Log::info('AppendTokenResponse before $next() call');
        Log::info('AppendTokenResponse incoming request payload', [
            'headers' => [
                'Content-Type' => $request->header('Content-Type'),
                'Accept' => $request->header('Accept'),
            ],
            'input' => $request->input(),
            'all' => $request->all(),
            'query' => $request->query(),
        ]);

//  $response = $next($request);
//        try {
//            DB::enableQueryLog();
//            $response = $next($request);
//         } catch (\Throwable $e) {
//             Log::info('AppendTokenResponse caught exception during $next()', [
//                 'message' => $e->getMessage(),
//                 'trace' => $e->getTraceAsString(),
//                 'queries' => DB::getQueryLog(),
//             ]);
//             throw $e;
//         }
 $response = $next($request);
    //    try {
       
    //     } catch (\Throwable $e) {
    //         Log::info('AppendTokenResponse caught exception during $next()', [
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         throw $e;
    //     }
    //     Log::debug('AppendTokenResponse after $next() call');

        $currentUrl = $request->url();

        if (strpos($currentUrl, 'oauth/token') !== false) {
            $content = json_decode($response->getContent(), true);
            $content['id_token'] = $this->generateIdToken($content['access_token']);
            Log::info('AppendTokenResponse id_token '. $content['id_token']);
            return response()->json($content, $response->getStatusCode(), $response->headers->all());
        }

        return $response;
    }
}