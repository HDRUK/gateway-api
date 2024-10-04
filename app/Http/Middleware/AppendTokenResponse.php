<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
        $response = $next($request);
        $currentUrl = $request->url();

        if (strpos($currentUrl, 'oauth/token') !== false) {
            $content = json_decode($response->getContent(), true);
            \Log::info('middleware content :: ' . json_encode($content));
            $content['id_token'] = $this->generateIdToken($content['access_token']);
            \Log::info('middleware id_token :: ' . json_encode($content['id_token']));
            // return response()->json($content, $response->getStatusCode(), $response->headers->all());
        }

        return $response;
    }
}