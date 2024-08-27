<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppendTokenResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {

        $response =  $next($request);
        \Log::info(json_encode($response));

        if ($response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);
            if (array_key_exists('access_token', $content)) {
                $content['id_token'] = $content['access_token'];
                $response->setContent(json_encode($content));
            }
        }

        // return $next($request);
        return $response;
    }
}