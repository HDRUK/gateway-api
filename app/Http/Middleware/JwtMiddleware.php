<?php

namespace App\Http\Middleware;

use Config;

use Closure;
use Illuminate\Http\Request;
use App\Models\AuthorisationCode;
use App\Http\Controllers\JwtController;
use App\Exceptions\UnauthorizedException;
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
            $isValidJwt = $jwtController->isValid();
            $isJwtInDb = AuthorisationCode::findRowByJwt($authorization);

            if (!$isValidJwt || !$isJwtInDb) {
                throw new UnauthorizedException();
            }

            $request->merge(['jwt' => $authorization]);
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
            $isJwtInDb = AuthorisationCode::findRowByJwt($jwtBearer);

            if (!$isValidJwt || !$isJwtInDb) {
                throw new UnauthorizedException();
            }

            $request->merge(['jwt' => $jwtBearer]);
            return $next($request);
        }

        throw new UnauthorizedException();
    }
}
