<?php

namespace App\Http\Middleware;

use Closure;

class CheckUserIdMatches
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
        $input = $request->all();
        if (!isset($input['jwt_user'])) {
            return response()->json(['message' => 'Unauthorized: User not found'], 401);
        }
        $user = $input['jwt_user'];
        $jwtUserIsAdmin = $user['is_admin'];

        $routeId = $request->route('id');

        if ($user['id'] == $routeId || $jwtUserIsAdmin) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden to access this user'], 403);
    }
}
