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
        Log::info('AppendTokenResponse middleware triggered', [
            'url' => $request->url(),
            'method' => $request->method(),
        ]);

    
        Log::info('AppendTokenResponse before $next() call');

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            Log::error('AppendTokenResponse caught exception during $next()', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; 
        }

        Log::info('AppendTokenResponse after $next() call');

        $currentUrl = $request->url();
        Log::info('AppendTokenResponse current URL check', ['url' => $currentUrl]);

        if (strpos($currentUrl, 'oauth/token') !== false) {
            Log::info('Matched oauth/token endpoint. Processing token response.');

            $content = json_decode($response->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::info('Failed to decode JSON from response', [
                    'error' => json_last_error_msg(),
                    'response_content' => $response->getContent(),
                ]);
                return $response;
            }

            if (!isset($content['access_token'])) {
                Log::info('Response missing access_token', ['content' => $content]);
                return $response;
            }

            try {
                $content['id_token'] = $this->generateIdToken($content['access_token']);
                Log::info('Generated id_token successfully.');
            } catch (\Throwable $e) {
                Log::info('Error generating id_token', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return $response; 
            }

            Log::info('Final modified response', ['content' => $content]);

            return response()->json($content, $response->getStatusCode(), $response->headers->all());
        }

        Log::info('URL did not match oauth/token. Returning unmodified response.');
        return $response;
    }
}
