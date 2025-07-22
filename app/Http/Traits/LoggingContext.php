<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;

trait LoggingContext
{
    public function getLoggingContext(Request $request)
    {
        $methodName = $this->getMethodName($request);

        $context = [
            'x-request-session-id' => $request->headers->all()['x-request-session-id'] ?? null,
            'url' => $request->fullUrl(),
            'http_method' => $request->getMethod(),
            'method_name' => $methodName,
        ];

        return $context;
    }

    protected function getMethodName($request): ?string
    {
        if (is_null($request->route())) {
            return null;
        }

        $route = app('router')->getRoutes()->match(
            app('request')->create($request->fullUrl(), $request->getMethod())
        );

        return $route->action['controller'] ?? null;
    }
}
