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

        $content = json_decode($response->getContent(), true);

        if (!empty($content['access_token'])) {

            $content['id_token'] = $content['access_token'];

            $response->setContent(json_encode($content));

        }

        return $response;
    }
}