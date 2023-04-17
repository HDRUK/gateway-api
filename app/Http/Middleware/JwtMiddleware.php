<?php

namespace App\Http\Middleware;

use Config;

use App\Http\Controllers\JwtController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    /**
     * @OA\SecurityScheme(
     *     type="http",
     *     description="Login with email and password to get the authentication token",
     *     name="Authorization",
     *     in="header",
     *     scheme="bearer",
     *     bearerFormat="JWT",
     *     securityScheme="bearerAuth",
     * )
     * 
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cater for auth token coming in via cookie - as sent originally during
        // the socialite oath flow
        if ($request->cookie('token')) {
            $authorization = $request->cookie('token');
            $jwtController = new JwtController();
            $jwtController->setJwt($authorization);
            
            if (!$jwtController->isValid()) {
                return response()->json([
                    Config::get('statuscodes.STATUS_UNAUTHORIZED.message'),
                ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
            }

            return $next($request);
        }

        // Otherwise fall back to bearer authorization header
        $autorization = $request->header('Authorization');
        $splitAutorization = explode(' ',$autorization);

        if (strtolower(trim($splitAutorization[0])) === 'bearer') {
            $jwtBearer = $splitAutorization[1];
            
            $jwtController = new JwtController();
            $jwtController->setJwt($jwtBearer);
            $isValidJwt = $jwtController->isValid();

            if (!$isValidJwt) {
                throw new \Exception("No valid authorization");
            }

        } else {
            return response()->json([
                Config::get('statuscodes.STATUS_UNAUTHORIZED.message'),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
            // LS - Removed, as this should consistently return an HTTP
            // Status code, rather than throw an exception
            // throw new \Exception("No authorization");
        }

        return $next($request);
    }
}
