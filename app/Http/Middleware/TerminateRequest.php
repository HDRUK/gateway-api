<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;

class TerminateRequest
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        if (is_null($request->route())) {
            return;
        }

        \Log::info('Request: ' . $request->route()->getActionName());
        \Log::info('Memory usage before manual GC: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB');

        $collected = gc_collect_cycles();
        \Log::info('Collected: ' . $collected);

        \Log::info('Memory usage after manual GC: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB');
    }
}
