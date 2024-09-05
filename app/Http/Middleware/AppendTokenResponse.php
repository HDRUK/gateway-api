<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Traits\CustomIdTokenTrait;
use Symfony\Component\HttpFoundation\Response;

class AppendTokenResponse
{
    use CustomIdTokenTrait;
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $currentUrl = $request->url();

        if (strpos($currentUrl, 'oauth/token') !== false) {
            $content = json_decode($response->getContent(), true);
            $content['id_token'] = $content['access_token'];
            
            \Log::info('middleware AppendTokenResponse access_token :: ' . json_encode($content['access_token']));

            $content['custom_id_token'] = $this->generateIdToken($content['access_token']);

            \Log::info('middleware AppendTokenResponse custom_id_token :: ' . json_encode($content['custom_id_token']));

            \Log::info('middleware AppendTokenResponse content :: ' . json_encode($content));

            return response()->json($content, $response->getStatusCode(), $response->headers->all());
        }

        return $response;
    }
}