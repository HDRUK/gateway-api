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
        // Resolved identity only - never $request->all(), which the
        // client fills.
        $user = $request->jwtUser();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized: User not found'], 401);
        }
        $jwtUserIsAdmin = $user['is_admin'];

        $routeId = $request->route('id');

        if ($user['id'] == $routeId || $jwtUserIsAdmin) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden to access this user'], 403);
    }
}
