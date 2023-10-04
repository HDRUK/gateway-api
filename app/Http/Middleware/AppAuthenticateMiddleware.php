<?php

namespace App\Http\Middleware;

use Config;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\AuthorisationCode;
use App\Exceptions\NotFoundException;
use App\Http\Controllers\JwtController;
use App\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class AppAuthenticateMiddleware
{
    /**
     * @OA\SecurityScheme(
     *     type="http",
     *     description="Authorise app using app id, client id, and client secret",
     *     name="AppAuthorization",
     *     in="header",
     *     scheme="bearer",
     *     bearerFormat="JWT",
     *     securityScheme="bearerAppAuth",
     * )
     * 
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Use bearer authorization header
        $authorization = $request->header('Authorization');
        $splitAuthorization = explode(' ', $authorization);

        # Check that the app id is in the app table
        $appId = $request;
        $app = validateAppId($appId);
        if (!$app) {
            throw new NotFoundException('App not found.');
        }

        # Check that the app id, client id, and client secret all match.
        $clientId = $app->client_id;
        $clientSecret = $app->client_secret;


        # If all successful, then pass the request on

        if (strtolower(trim($splitAuthorization[0])) === 'bearer') {
            $jwtBearer = $splitAuthorization[1];
            
            $jwtController = new JwtController();
            $jwtController->setJwt($jwtBearer);
            $isValidJwt = $jwtController->isValid();
            $isJwtInDb = AuthorisationCode::findRowByJwt($jwtBearer);

            if (!$isValidJwt && !$isJwtInDb) {
                throw new UnauthorizedException();
            }
            
            $request->merge(['jwt' => $jwtBearer]);

            $payloadJwt = $jwtController->decode();
            $userJwt = $payloadJwt['user'];

            $user = $this->validateUserId((int) $userJwt['id']);

            if (!$user) {
                throw new NotFoundException('User not found.');
            }

            // $request->merge(
            //     [
            //         'jwt_user' => [
            //             'id' => $user->id,
            //             'name' => $user->name,
            //             'email' => $user->email,
            //             'is_admin' => $user->is_admin,
            //             ]
            //         ]
            //     );
            return $next($request);
        }

        throw new UnauthorizedException();
    }

    private function validateAppId(int $appId)
    {
        return Application::find($appId);
    }
}

