<?php

namespace App\Http\Middleware;

use Closure;
// use Illuminate\Http\Request;
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
        \Log::info('Authenticate :: ' . json_encode($currentUrl));
        
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
