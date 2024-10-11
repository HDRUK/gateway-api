<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        $input = $request->all();
        $jwtUserIsAdminId = $input['jwt_user']['is_admin'];
        $access = explode("|", $data);

        if ($jwtUserIsAdminId) {
            return $next($request);
        }

        if (!count($access)) {
            throw new UnauthorizedException();
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
