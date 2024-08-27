<?php

namespace App\Http\Middleware;

use Closure;

class AppendTokenResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $response =  $next($request);

        $content = json_decode($response->content(), true);

        if (!empty($content['access_token'])) {

            $content['id_token'] = $content['access_token'];

            $response->setContent($content);

        }

        return $response;
    }
}