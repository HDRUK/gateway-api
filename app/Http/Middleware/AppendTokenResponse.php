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
        $response = $next($request);
        $currentUrl = $request->url();

        if (strpos($currentUrl, 'oauth/token') !== false) {
            $content = json_decode($response->getContent(), true);
            $content['id_token'] = $this->generateIdToken($content['access_token']);
            Log::info('MiddlewareAppended id_token to response', [
                'user_id' => $request->user()->id ?? null,
                'id_token' => $content['id_token'] ?? null,
                'content' => $content,
            ]);
            return response()->json($content, $response->getStatusCode(), $response->headers->all());
        }

        return $response;
    }
}
