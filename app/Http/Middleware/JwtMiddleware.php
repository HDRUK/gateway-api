<?php

namespace App\Http\Middleware;

use Closure;
use App\Auth\TokenDenylist;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use App\Exceptions\NotFoundException;
use App\Http\Controllers\JwtController;
use App\Http\Traits\UserRolePermissions;
use App\Exceptions\UnauthorizedException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        // the socialite oauth flow
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
            $this->assertNotRevoked($payloadJwt);
            $userJwt = $payloadJwt['user'];

            $user = $this->validateUserId((int) $userJwt['id']);

            if (!$user) {
                throw new NotFoundException('User not found.');
            }

            $request->attributes->set(
                'jwt_user',
                $this->buildJwtUserPayload($request, $user),
            );

            $this->authenticateAs($request, $user);

            return $next($request);
        }

        if ($request->hasHeader('x-application-id') && $request->hasHeader('x-client-id')) {
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
                $appId . ':' . $clientId . ':' . config('auth.private_salt') . ':' . config('auth.private_salt_2'),
                $clientSecret
            ))) {
                throw new UnauthorizedException('The credentials provided are invalid');
            }

            # Pass on the application's PK, not the public app_id.
            # Every consumer of this looks the application back up by `id`,
            # and app_id is a 40-character random string. When casting that to
            # int yielded 0, so the lookup never matched.
            $request->merge(
                [
                    'app' => [
                        'id' => $app->id,
                    ]
                ]
            );

            return $next($request);
        }

        // Otherwise fall back to bearer authorization header
        if ($request->header('Authorization')) {
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
                $this->assertNotRevoked($payloadJwt);
                $userJwt = $payloadJwt['user'];

                $user = $this->validateUserId((int) $userJwt['id']);

                if (!$user) {
                    throw new NotFoundException('User not found.');
                }

                $request->attributes->set(
                    'jwt_user',
                    $this->buildJwtUserPayload($request, $user),
                );

                $this->authenticateAs($request, $user);

                return $next($request);
            }
        }

        throw new UnauthorizedException();
    }


    /**
     * A signature-valid token is not necessarily a live one: it may have
     * been withdrawn by logout, or by an admin revoking all tokens, before
     * reaching its expiry.
     */
    private function assertNotRevoked(array $claims): void
    {
        if (app(TokenDenylist::class)->rejects($claims)) {
            throw new UnauthorizedException('Token has been revoked.');
        }
    }

    /**
     * Publish the authenticated user through Laravel's own plumbing, in
     * addition to the jwt_user payload above.
     *
     * Both of these are request-scoped. We deliberately do NOT call
     * Auth::shouldUse('jwt'): that writes auth.defaults.guard, which is
     * process-global, and Octane's FlushAuthenticationState only calls
     * forgetGuards() - clearing resolved guards but not the default
     * driver. Flipping it here would leak into every later request on the
     * worker, including web and Passport routes.
     */
    private function authenticateAs(Request $request, User $user): void
    {
        $request->setUserResolver(fn () => $user);

        Auth::guard('jwt')->setUser($user);
    }

    private function validateUserId(int $userId)
    {
        return User::find($userId);
    }

    private function buildJwtUserPayload(Request $request, User $user): array
    {
        $jwtUser = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
        ];

        if ($this->routeRequiresRolePerms($request)) {
            $jwtUser['role_perms'] = $this->getUserRolePerms($user->id);
        }

        return $jwtUser;
    }

    private function routeRequiresRolePerms(Request $request): bool
    {
        $route = $request->route();
        if (!$route) {
            return true;
        }

        $controllerAction = (string) ($route->getAction()['controller'] ?? '');
        if ($controllerAction === 'App\\Http\\Controllers\\Api\\V1\\UserController@show') {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if ($middleware === 'check.access' || str_starts_with($middleware, 'check.access:')) {
                return true;
            }
        }

        return true;
    }
}
