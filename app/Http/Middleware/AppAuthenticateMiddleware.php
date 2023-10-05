<?php

namespace App\Http\Middleware;

use Config;

use Closure;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Exceptions\NotFoundException;
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
        // $authorization = $request->header('Authorization');
        // $splitAuthorization = explode(' ', $authorization);

        # Check that the app id is in the app table
        $appId = $request['app_id'];
        // var_dump($appId);
        $app = Application::where('app_id', $appId)->first();

        if (!$app) {
            throw new NotFoundException('App not found.');
        }

        # Check that the app id, client id, and client secret all match. Throw an exception if not matching.
        $clientId = $app->client_id;
        $clientSecret = $app->client_secret;
        // var_dump($app);

        if (!($clientId == $request['client_id'] && $clientSecret == $request['client_secret'])) {
            throw new UnauthorizedException();
        }

        # Otherwise, it's all successful, so pass the request on
        return $next($request);
    }

}

