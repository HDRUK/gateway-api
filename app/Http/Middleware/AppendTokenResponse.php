<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log; 
use App\Http\Traits\CustomIdTokenTrait;
use Symfony\Component\HttpFoundation\Response;

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
       Log::debug('AppendTokenResponse before $next() call');
        Log::debug('AppendTokenResponse incoming request payload', [
            'headers' => [
                'Content-Type' => $request->header('Content-Type'),
                'Accept' => $request->header('Accept'),
            ],
            'body' => $request,
            'query' => $request->query(),
        ]);
        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            Log::debug('AppendTokenResponse caught exception during $next()', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; 
        }
        Log::debug('AppendTokenResponse after $next() call');

        $currentUrl = $request->url();

        if (strpos($currentUrl, 'oauth/token') !== false) {
            $content = json_decode($response->getContent(), true);
            $content['id_token'] = $this->generateIdToken($content['access_token']);
            return response()->json($content, $response->getStatusCode(), $response->headers->all());
        }

        return $response;
    }
}