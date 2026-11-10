<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\CheckAccessMiddleware;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Guards the route level the current design depends on.
 *
 * CheckAccessMiddleware reads `jwt_user` straight out of the request
 * input bag, which the client controls. It is only safe because
 * JwtMiddleware always runs first and overwrites whatever the client
 * sent. That safety is a property of the route configuration, not of the
 * code — nothing stops someone adding a `check.access` route without
 * `jwt.verify` and silently creating a privileged escalation hole.
 *
 * This test makes that invariant explicit and enforced. It should keep
 * passing throughout the auth migration, and can be deleted only once
 * authorisation reads from a guard rather than from request input.
 */
class RouteAuthInvariantTest extends TestCase
{
    /**
     * Routes are registered under both an alias and, in some groups, the
     * class name. Match either, with or without `:parameters`.
     */
    private const AUTHENTICATES = ['jwt.verify', JwtMiddleware::class];

    private const CHECKS_ACCESS = ['check.access', 'check.access.userId', CheckAccessMiddleware::class];

    /**
     * The test harness bootstraps the console kernel, so the HTTP route
     * files are not loaded until a request is actually dispatched. Force
     * that before inspecting the route collection — otherwise every
     * assertion below passes vacuously against an empty collection.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function hasMiddleware(RoutingRoute $route, array $needles): bool
    {
        // $route->middleware() - NOT gatherMiddleware(), which instantiates
        // the controller to collect its middleware. Several SSO controllers
        // take an AuthorizationServer, so that construction needs Passport
        // keys and blows up anywhere they are absent (CI). Route and group
        // middleware is all this needs, and it is already on the action.
        foreach ($route->middleware() as $middleware) {
            if (!is_string($middleware)) {
                continue;
            }

            $name = explode(':', $middleware, 2)[0];

            if (in_array($name, $needles, true)) {
                return true;
            }
        }

        return false;
    }

    public function test_every_authorisation_checked_route_authenticates_first(): void
    {
        $offending = [];

        foreach (Route::getRoutes() as $route) {
            if (!$this->hasMiddleware($route, self::CHECKS_ACCESS)) {
                continue;
            }

            if ($this->hasMiddleware($route, self::AUTHENTICATES)) {
                continue;
            }

            $offending[] = implode('|', $route->methods()) . ' ' . $route->uri();
        }

        $this->assertSame(
            [],
            $offending,
            'Route(s) apply an access check without jwt.verify in front of them. '
            . 'CheckAccessMiddleware trusts request input for `jwt_user`, so without '
            . "JwtMiddleware overwriting it first a client can assert its own is_admin flag:\n"
            . implode("\n", $offending),
        );
    }

    /**
     * A sanity check on the test above. The route collection is empty
     * until a request has been dispatched, and gatherMiddleware() returns
     * aliases rather than class names. Either trap would make the
     * invariant test pass against nothing at all.
     */
    public function test_the_invariant_is_actually_being_checked(): void
    {
        $authenticated = 0;
        $accessChecked = 0;

        foreach (Route::getRoutes() as $route) {
            if ($this->hasMiddleware($route, self::AUTHENTICATES)) {
                $authenticated++;
            }

            if ($this->hasMiddleware($route, self::CHECKS_ACCESS)) {
                $accessChecked++;
            }
        }

        $this->assertGreaterThan(100, $authenticated, 'Expected jwt.verify on a large share of routes.');
        $this->assertGreaterThan(100, $accessChecked, 'Expected an access check on a large share of routes.');
    }
}
