<?php

namespace App\Http\Middleware;

use Config;

use Closure;
use Hash;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateIntegrationMiddleware
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
        # Check that the app id is in the app table
        $appId = $request['app_id'];
        $app = Application::where('app_id', $appId)->first();
        if (!$app) {
            throw new NotFoundException('App not found.');
        }

        # Check that the app id and client id both match. Throw an exception if not matching.
        $clientId = $app->client_id;
        $clientSecret = $app->client_secret;
        if (!($clientId == $request['client_id'])) {
            throw new UnauthorizedException();
        }

        # Check the client secret
        if (!Hash::check($appId . ':' . $clientId, $clientSecret)) {
            throw new UnauthorizedException();
        }

        # Otherwise, it's all successful, so pass the request on
        return $next($request);
    }

}

