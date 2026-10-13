<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Inventory of routes that rely on the CheckAccess trait for
 * authorisation but carry no check.access middleware.
 *
 * On those routes CheckAccessMiddleware never runs, so the access context
 * it would publish is empty. checkAccessTeam() then skips its
 * role/permission comparison entirely and falls out of the bottom
 * returning null - and callers ignore the return value, relying on
 * exceptions - so the request proceeds. The effective authorisation
 * becomes "is a member of the team and holds at least one role in it",
 * not "holds the role or permission this action requires".
 *
 * Routes taking the 'user' branch are unaffected: checkAccessUser()
 * compares the caller's id to the record's owner and throws on a
 * mismatch, independently of the access context.
 *
 * This is a RATCHET, not an approval. The list may shrink - adding
 * check.access to a route is the fix - but it must not grow. A new
 * entry here means a new endpoint whose required permission is not
 * actually enforced.
 *
 * @see \App\Http\Traits\CheckAccess
 * @see \Tests\Feature\Auth\AccessContextSourceTest
 */
class RouteAccessCoverageTest extends TestCase
{
    /**
     * Routes known to rely on the trait alone, as at the Phase 5 audit.
     *
     * @var array<int, string>
     */
    private const KNOWN_WITHOUT_CHECK_ACCESS = [
        'DELETE api/services/federations/delete/{pid}',
        'DELETE api/v1/tools/{id}',
        'DELETE api/v2/collections/{id}',
        'DELETE api/v2/teams/{teamId}/collections/{id}',
        'DELETE api/v2/teams/{teamId}/publications/{id}',
        'DELETE api/v2/users/{userId}/collections/{id}',
        'DELETE api/v2/users/{userId}/publications/{id}',
        'DELETE api/v2/users/{userId}/tools/{id}',
        'GET api/v2/teams/{teamId}/collections/count/{field}',
        'GET api/v2/teams/{teamId}/collections/{id}',
        'GET api/v2/teams/{teamId}/datasets/count/{field}',
        'GET api/v2/teams/{teamId}/datasets/status/{status}',
        'GET api/v2/teams/{teamId}/dur/count/{field}',
        'GET api/v2/teams/{teamId}/dur/status/{status}',
        'GET api/v2/teams/{teamId}/dur/{id}',
        'GET api/v2/teams/{teamId}/publications/count/{field}',
        'GET api/v2/teams/{teamId}/publications/status/{status}',
        'GET api/v2/teams/{teamId}/publications/{id}',
        'GET api/v2/teams/{teamId}/tools/count/{field}',
        'GET api/v2/teams/{teamId}/tools/status/{status}',
        'GET api/v2/teams/{teamId}/tools/{id}',
        'GET api/v2/users/{userId}/collections/count/{field}',
        'GET api/v2/users/{userId}/publications/count/{field}',
        'GET api/v2/users/{userId}/publications/status/{status}',
        'GET api/v2/users/{userId}/publications/{id}',
        'GET api/v2/users/{userId}/tools/count/{field}',
        'GET api/v2/users/{userId}/tools/status/{status}',
        'GET api/v2/users/{userId}/tools/{id}',
        'PATCH api/v1/teams/{teamId}',
        'PATCH api/v1/tools/{id}',
        'PATCH api/v2/collections/{id}',
        'PATCH api/v2/teams/{teamId}/collections/{id}',
        'PATCH api/v2/teams/{teamId}/publications/{id}',
        'PATCH api/v2/users/{userId}/collections/{id}',
        'PATCH api/v2/users/{userId}/publications/{id}',
        'PATCH api/v2/users/{userId}/tools/{id}',
        'POST api/services/federations',
        'POST api/v1/tools',
        'POST api/v2/teams/{teamId}/publications',
        'POST api/v2/users/{userId}/publications',
        'POST api/v2/users/{userId}/tools',
        'PUT api/services/federations/update/{pid}',
        'PUT api/v1/tools/{id}',
        'PUT api/v2/collections/{id}',
        'PUT api/v2/teams/{teamId}/collections/{id}',
        'PUT api/v2/teams/{teamId}/publications/{id}',
        'PUT api/v2/users/{userId}/collections/{id}',
        'PUT api/v2/users/{userId}/publications/{id}',
        'PUT api/v2/users/{userId}/tools/{id}',
    ];

    /**
     * The harness bootstraps the console kernel, so HTTP routes are not
     * loaded until a request is dispatched.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->get('/_force_route_loading');
    }

    /**
     * True when the controller method backing a route calls into the
     * CheckAccess trait.
     */
    private function usesCheckAccessTrait(string $action): bool
    {
        if (!str_contains($action, '@')) {
            return false;
        }

        [$class, $method] = explode('@', $action, 2);

        if (!class_exists($class) || !method_exists($class, $method)) {
            return false;
        }

        $reflection = new ReflectionMethod($class, $method);
        $file = $reflection->getFileName();

        if ($file === false) {
            return false;
        }

        $body = implode('', array_slice(
            file($file),
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1,
        ));

        return str_contains($body, 'checkAccess(')
            || str_contains($body, 'checkAccessCollaborators(');
    }

    /**
     * @return array<int, string>
     */
    private function routesRelyingOnTraitAlone(): array
    {
        $found = [];

        foreach (Route::getRoutes() as $route) {
            $action = (string) ($route->getAction()['controller'] ?? '');

            if (!$this->usesCheckAccessTrait($action)) {
                continue;
            }

            $hasCheckAccess = false;
            foreach ($route->gatherMiddleware() as $middleware) {
                if (!is_string($middleware)) {
                    continue;
                }
                $name = explode(':', $middleware, 2)[0];
                if ($name === 'check.access' || $name === \App\Http\Middleware\CheckAccessMiddleware::class) {
                    $hasCheckAccess = true;
                    break;
                }
            }

            if ($hasCheckAccess) {
                continue;
            }

            foreach ($route->methods() as $verb) {
                if ($verb === 'HEAD') {
                    continue;
                }
                $found[] = $verb . ' ' . $route->uri();
            }
        }

        return array_values(array_unique($found));
    }

    public function test_no_new_routes_rely_on_the_trait_alone(): void
    {
        $actual = $this->routesRelyingOnTraitAlone();

        $new = array_diff($actual, self::KNOWN_WITHOUT_CHECK_ACCESS);

        $this->assertSame(
            [],
            array_values($new),
            "New route(s) use the CheckAccess trait without check.access middleware, so the "
            . "permission they appear to require is not actually enforced. Add the appropriate "
            . "check.access middleware, or add them here deliberately:\n  "
            . implode("\n  ", $new),
        );
    }

    /**
     * Guards against the inventory silently going stale - if the matching
     * stops working, the ratchet above would pass against nothing.
     */
    public function test_the_inventory_is_actually_being_measured(): void
    {
        $this->assertGreaterThan(
            20,
            count($this->routesRelyingOnTraitAlone()),
            'Expected to still detect the known routes; the detection has probably broken.',
        );
    }

    /**
     * Fixed entries should disappear from the list, not linger. This is
     * informational: it fails only if the baseline is wildly out of date.
     */
    public function test_the_inventory_has_not_drifted_wholesale(): void
    {
        $fixed = array_diff(self::KNOWN_WITHOUT_CHECK_ACCESS, $this->routesRelyingOnTraitAlone());

        $this->assertLessThan(
            count(self::KNOWN_WITHOUT_CHECK_ACCESS),
            count($fixed),
            'Every known route has changed; the baseline needs regenerating.',
        );
    }
}
