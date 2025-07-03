<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use App\Exceptions\NotFoundException;
use App\Http\Controllers\JwtController;
use App\Http\Traits\UserRolePermissions;
use App\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    use UserRolePermissions;

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

            if (!$isValidJwt) {
                throw new UnauthorizedException();
            }

            $request->merge(['jwt' => $authorization]);

            $payloadJwt = $jwtController->decode();
            $userJwt = $payloadJwt['user'];

            $user = $this->validateUserId((int) $userJwt['id']);

            if (!$user) {
                throw new NotFoundException('User not found.');
            }

            $request->merge(
                [
                    'jwt_user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_admin' => $user->is_admin,
                        'role_perms' => $this->getUserRolePerms($user->id),
                    ],
                ],
            );

            return $next($request);
        }

        // Otherwise fall back to bearer authorization header
        $authorization = $request->header('Authorization');
        $splitAuthorization = explode(' ', $authorization);

        if (strtolower(trim($splitAuthorization[0])) === 'bearer') {
            $jwtBearer = $splitAuthorization[1];

            $jwtController = new JwtController();
            $jwtController->setJwt($jwtBearer);
            $isValidJwt = $jwtController->isValid();

            if (!$isValidJwt) {
                throw new UnauthorizedException();
            }

            $request->merge(['jwt' => $jwtBearer]);

            $payloadJwt = $jwtController->decode();
            $userJwt = $payloadJwt['user'];

            $user = $this->validateUserId((int) $userJwt['id']);

            if (!$user) {
                throw new NotFoundException('User not found.');
            }

            $request->merge(
                [
                    'jwt_user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_admin' => $user->is_admin,
                        'role_perms' => $this->getUserRolePerms($user->id),
                    ],
                ],
            );
            return $next($request);
        }

        throw new UnauthorizedException();
    }


    private function validateUserId(int $userId)
    {
        return User::find($userId);
    }
}
