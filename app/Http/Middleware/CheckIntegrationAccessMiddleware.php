<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Application;
use App\Exceptions\IntegrationPermissionException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIntegrationAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'permissions|roles', string $data = ''): Response
    {
        // Owing to a previous middleware authenticating the application, we can assume
        // that this has already happened, so, we just check application permissions
        // from here on
        $application = Application::with('permissions')
            ->where('app_id', $request->header('x-application-id'))
            ->where('client_id', $request->header('x-client-id'))
            ->first()
            ->toArray();

        if (!$application) {
            throw new IntegrationPermissionException('No known integration matches supplied credentials');
        }

        if (!$application['enabled']) {
            throw new IntegrationPermissionException('Application has not been enabled!');
        }

        foreach ($application['permissions'] as $perm) {
            if ($perm['name'] === $data) {
                return $next($request);
            }
        }

        throw new IntegrationPermissionException('Application permissions do not allow this request');
    }
}
