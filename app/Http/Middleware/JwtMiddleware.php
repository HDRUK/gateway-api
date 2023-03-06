<?php

namespace App\Http\Middleware;

use App\Http\Controllers\JwtController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

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
            throw new \Exception("No authorization");
        }

        return $next($request);
    }
}
