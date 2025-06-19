<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Exceptions\IntegrationPermissionException;
use App\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class CheckAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'permissions|roles', string $data = ''): Response
    {
        $access = explode("|", $data);

        if ($request->hasHeader('x-application-id') && $request->hasHeader('x-client-id')) {
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

            $checkingPerms = array_diff($access, array_column($application['permissions'], 'name'));
            if (empty($checkingPerms)) {
                $request->merge(
                    [
                        'middleware' => [
                            'roles' => [],
                            'perms' => $access,
                        ],
                    ],
                );

                return $next($request);
            }

            throw new IntegrationPermissionException('Application permissions do not allow this request');
        }

        $input = $request->all();
        $jwtUserIsAdminId = $input['jwt_user']['is_admin'];
        $teamId = $request->route('teamId');

        if ($jwtUserIsAdminId) {
            return $next($request);
        }

        if (!count($access)) {
            throw new UnauthorizedException();
        }

        $currentUserRoles = [];
        $currentUserPermissions = [];
        if ($teamId) {
            if (isset($input['jwt_user']['role_perms']['teams'][(string) $teamId])) {
                $currentUserRoles = array_unique(array_merge($input['jwt_user']['role_perms']['extra']['roles'], $input['jwt_user']['role_perms']['teams'][(string) $teamId]['roles']));
                $currentUserPermissions = array_unique(array_merge($input['jwt_user']['role_perms']['extra']['perms'], $input['jwt_user']['role_perms']['teams'][(string) $teamId]['perms']));
            } else {
                $currentUserRoles = array_unique($input['jwt_user']['role_perms']['extra']['roles']);
                $currentUserPermissions = array_unique($input['jwt_user']['role_perms']['extra']['perms']);
            }
        } else {
            $currentUserRoles = $input['jwt_user']['role_perms']['summary']['roles'];
            $currentUserPermissions = $input['jwt_user']['role_perms']['summary']['perms'];
        }

        if ($type === 'roles') {
            $checkingRoles = array_diff($access, $currentUserRoles);
            if (!empty($checkingRoles)) {
                throw new UnauthorizedException();
            }
        }

        if ($type === 'permissions') {
            $checkingPermissions = array_diff($access, $currentUserPermissions);

            if (!empty($checkingPermissions)) {
                throw new UnauthorizedException();
            }
        }

        $request->merge(
            [
                'middleware' => [
                    'roles' => ($type === 'roles') ? $access : [],
                    'perms' => ($type === 'permissions') ? $access : [],
                ],
            ],
        );

        return $next($request);
    }
}
