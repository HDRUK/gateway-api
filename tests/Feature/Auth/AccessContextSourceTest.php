<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\CheckAccessMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Finishes what Phase 2 started: the `middleware` access context moves
 * off the request INPUT bag.
 *
 * CheckAccessMiddleware records which roles/permissions a route demanded
 * so that CheckAccess::checkAccess() can re-check them against the team
 * the record actually belongs to. That context was merged into the input
 * bag, which the client fills - the same arrangement that was removed for
 * jwt_user. It now lives on the attributes bag, read via
 * $request->accessContext().
 *
 * This matters more than the jwt_user case did, because the context is
 * absent on routes that have no check.access middleware, so there the
 * client's value was the only value.
 *
 * @see \Tests\Feature\Auth\JwtUserSourceTest
 */
class AccessContextSourceTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private User $user;

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->authorisationUser();
        $this->user = User::where('email', config(self::CFG_TEST_EMAIL))->firstOrFail();
    }

    private function runMiddleware(Request $request, string $type, string $data): Request
    {
        $request->attributes->set('jwt_user', [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'is_admin' => 0,
            'role_perms' => [
                'cohort' => ['roles' => []],
                'extra' => ['roles' => [], 'perms' => []],
                'summary' => ['roles' => ['developer'], 'perms' => ['tools.update']],
            ],
        ]);

        $seen = null;

        (new CheckAccessMiddleware())->handle($request, function (Request $passed) use (&$seen) {
            $seen = $passed;

            return response()->json(['ok' => true]);
        }, $type, $data);

        return $seen;
    }

    public function test_access_context_is_published_on_the_attributes_bag(): void
    {
        $request = $this->runMiddleware(
            Request::create('/api/v1/tools/1', 'PUT'),
            'permissions',
            'tools.update',
        );

        $this->assertSame(
            ['roles' => [], 'perms' => ['tools.update']],
            $request->attributes->get('middleware'),
        );
    }

    public function test_access_context_is_absent_from_the_input_bag(): void
    {
        $request = $this->runMiddleware(
            Request::create('/api/v1/tools/1', 'PUT'),
            'permissions',
            'tools.update',
        );

        $this->assertNull($request->input('middleware'));
        $this->assertArrayNotHasKey('middleware', $request->all());
    }

    /**
     * The whole point: a client asserting its own access context cannot
     * influence what the application later re-checks.
     */
    public function test_client_supplied_access_context_is_ignored(): void
    {
        $request = $this->runMiddleware(
            Request::create('/api/v1/tools/1', 'PUT', [
                'middleware' => ['roles' => ['custodian.team.admin'], 'perms' => ['tools.delete']],
            ]),
            'permissions',
            'tools.update',
        );

        $this->assertSame(
            ['roles' => [], 'perms' => ['tools.update']],
            $request->accessContext(),
            'The context must come from the route definition, never the request body.',
        );
    }

    public function test_the_macro_returns_an_empty_array_when_unset(): void
    {
        $this->assertSame([], Request::create('/api/v1/tools/1', 'PUT')->accessContext());
    }
}
