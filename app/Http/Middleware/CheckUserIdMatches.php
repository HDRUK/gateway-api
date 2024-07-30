<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

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
        $user = $input['jwt_user'];

        $routeId = $request->route('id');

        if ($user && $user->id == $routeId) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden to edit this user'], 403);
    }
}
