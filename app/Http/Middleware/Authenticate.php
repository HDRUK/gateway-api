<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        $currentUrl = $request->url();

        \Log::info('Authenticate $currentUrl :: ' . json_encode($currentUrl));
        \Log::info('Authenticate oauth/logoutme :: ' . json_encode(strpos($currentUrl, 'oauth/logoutme') !== false));
        \Log::info('Authenticate rquest :: ' . json_encode(strpos($currentUrl, 'rquest') !== false));
        \Log::info('Authenticate if :: ' . json_encode((! $request->expectsJson() && (strpos($currentUrl, 'oauth/logoutme') !== false || strpos($currentUrl, 'rquest') !== false))));

        if (! $request->expectsJson() && (strpos($currentUrl, 'oauth/logoutme') !== false || strpos($currentUrl, 'rquest') !== false)) {
            return route('login');
        }
    }
}
