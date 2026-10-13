<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\JwtController;
use App\Http\Middleware\JwtMiddleware;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Phase 1 of the auth migration: a real guard alongside the existing
 * request-input-bag approach.
 *
 * The guard is purely additive. JwtMiddleware keeps merging `jwt_user`
 * exactly as before, so no existing consumer changes; it additionally
 * resolves the authenticated user onto the request and the `jwt` guard,
 * so new and migrated code can use $request->user() instead of digging
 * through $request->input('jwt_user').
 *
 * The guard RESOLVES, it does not ENFORCE. Returning null on a bad token
 * is correct guard behaviour; rejecting the request remains the
 * middleware's job, which keeps the existing 401/404 semantics intact.
 *
 * @see \App\Auth\JwtUserResolver
 */
class JwtGuardTest extends TestCase
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

    private function tokenFor(User $user): string
    {
        return (new JwtController())->generateToken($user->id);
    }

    private function bearerRequest(?string $token = null): Request
    {
        $request = Request::create('/api/v1/users', 'GET');

        if ($token !== null) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $request;
    }

    /**
     * Resolve through the real guard.
     *
     * RequestGuard::user() takes no arguments - it reads the request bound
     * in the container - so the request has to be swapped in, and any
     * previously resolved guard forgotten, to exercise the wiring properly
     * rather than a cached result.
     */
    private function resolveViaGuard(Request $request): ?User
    {
        $this->app->instance('request', $request);
        Auth::forgetGuards();

        return Auth::guard('jwt')->user();
    }

    /**
     * Run the middleware and hand back the request the next closure saw.
     */
    private function passThrough(Request $request): Request
    {
        $seen = null;

        (new JwtMiddleware())->handle($request, function (Request $passed) use (&$seen) {
            $seen = $passed;

            return response()->json(['ok' => true]);
        });

        return $seen;
    }

    public function test_the_jwt_guard_is_registered(): void
    {
        $this->assertNotNull(Auth::guard('jwt'));
    }

    public function test_guard_resolves_the_user_from_a_bearer_token(): void
    {
        $request = $this->bearerRequest($this->tokenFor($this->user));

        $this->assertSame($this->user->id, $this->resolveViaGuard($request)?->id);
    }

    public function test_guard_resolves_the_user_from_the_token_cookie(): void
    {
        $request = $this->bearerRequest();
        $request->cookies->set('token', $this->tokenFor($this->user));

        $this->assertSame($this->user->id, $this->resolveViaGuard($request)?->id);
    }

    /**
     * A guard reports "nobody is authenticated", it does not throw. The
     * middleware is what turns that into a 401.
     */
    public function test_guard_returns_null_for_a_bad_token_rather_than_throwing(): void
    {
        $request = $this->bearerRequest('not-a-real-token');

        $this->assertNull($this->resolveViaGuard($request));
    }

    public function test_guard_returns_null_when_no_credentials_are_present(): void
    {
        $this->assertNull($this->resolveViaGuard($this->bearerRequest()));
    }

    /**
     * Integration requests authenticate an application, not a person, so
     * there is deliberately no user to resolve. Authorisation for those
     * continues through CheckAccessMiddleware's application branch.
     */
    public function test_guard_returns_null_for_integration_credentials(): void
    {
        $application = Application::where('id', 1)->firstOrFail();

        $request = $this->bearerRequest();
        $request->headers->set('x-application-id', $application->app_id);
        $request->headers->set('x-client-id', $application->client_id);

        $this->assertNull($this->resolveViaGuard($request));
    }

    public function test_middleware_resolves_the_user_onto_the_request(): void
    {
        $request = $this->passThrough($this->bearerRequest($this->tokenFor($this->user)));

        $this->assertSame($this->user->id, $request->user()?->id);
    }

    public function test_middleware_resolves_the_user_onto_the_jwt_guard(): void
    {
        $this->passThrough($this->bearerRequest($this->tokenFor($this->user)));

        $this->assertSame($this->user->id, Auth::guard('jwt')->user()?->id);
    }

    /**
     * Backwards compatibility is the whole point of this phase: the 74
     * existing consumers of jwt_user must not notice anything.
     */
    public function test_jwt_user_is_still_populated_unchanged(): void
    {
        $request = $this->passThrough($this->bearerRequest($this->tokenFor($this->user)));

        $jwtUser = $request->input('jwt_user');

        $this->assertSame($this->user->id, $jwtUser['id']);
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'email', 'is_admin', 'role_perms'],
            array_keys($jwtUser),
        );
    }

    /**
     * Guards against a cross-request state leak specific to this app.
     *
     * Auth::shouldUse() sets config('auth.defaults.guard'), which is
     * process-global. This project runs on Octane (frankenphp), whose
     * FlushAuthenticationState listener only calls forgetGuards() —
     * that clears resolved guard INSTANCES but leaves the default driver
     * alone, and config/octane.php has an empty `flush` list. Flipping
     * the default guard in middleware would therefore persist on the
     * worker and silently change authentication for every later request,
     * including web and Passport/OIDC routes.
     *
     * Hence the middleware sets the user resolver and the jwt guard
     * directly and never touches the default.
     */
    public function test_middleware_does_not_change_the_default_guard(): void
    {
        $before = config('auth.defaults.guard');

        $this->passThrough($this->bearerRequest($this->tokenFor($this->user)));

        $this->assertSame($before, config('auth.defaults.guard'));
        $this->assertSame('web', config('auth.defaults.guard'));
    }
}
