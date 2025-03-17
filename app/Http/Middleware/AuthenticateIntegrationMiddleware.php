<?php

namespace App\Http\Middleware;

use Closure;
use Hash;
use App\Models\Application;
use Illuminate\Http\Request;
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
        if (!($request->header('x-application-id') && $request->header('x-client_id'))) {
            throw new UnauthorizedException('Please provide a x-application-id and x-client-id in your headers');
        }

        # Check that the app id is in the app table
        $appId = $request->header('x-application-id');
        $app = Application::where('app_id', $appId)->first();

        if (!$app) {
            throw new UnauthorizedException('No known integration matches the credentials provided');
        }

        # Check that the app id and client id both match, and check the client secret. Throw an exception if not matching.
        $clientId = $app->client_id;
        $clientSecret = $app->client_secret;
        if (!($clientId == $request->header('x-client-id') && Hash::check(
            $appId . ':' . $clientId . ':' . env('APP_AUTH_PRIVATE_SALT') . ':' . env('APP_AUTH_PRIVATE_SALT_2'),
            $clientSecret
        ))
        ) {
            throw new UnauthorizedException('The credentials provided are invalid');
        }

        $request->merge(
            [
                'app' => [
                    'id' => (int) $appId,
                ]
            ]
        );

        # Otherwise, it's all successful, so pass the request on
        return $next($request);
    }

}
