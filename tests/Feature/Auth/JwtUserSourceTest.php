<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\JwtController;
use App\Http\Middleware\JwtMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Phase 2 of the auth migration: authenticated identity stops travelling
 * in the request INPUT bag.
 *
 * JwtMiddleware used to $request->merge() the jwt_user payload, putting
 * server-derived auth state into the same bag the client fills. That was
 * only safe because the merge overwrote whatever the client sent, and
 * because every check.access route happened to have jwt.verify in front
 * of it - a property of route configuration rather than of the code.
 *
 * It now lives in the Symfony attributes bag, which nothing outside the
 * application can write to. Consumers read it through $request->jwtUser(),
 * so the ~300 call sites keep the same array shape and the same defaults.
 *
 * @see \App\Http\Middleware\JwtMiddleware
 */
class JwtUserSourceTest extends TestCase
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

    private function passThrough(Request $request): Request
    {
        $seen = null;

        (new JwtMiddleware())->handle($request, function (Request $passed) use (&$seen) {
            $seen = $passed;

            return response()->json(['ok' => true]);
        });

        return $seen;
    }

    private function authenticated(array $body = []): Request
    {
        $request = Request::create('/api/v1/users', 'GET', $body);
        $request->headers->set(
            'Authorization',
            'Bearer ' . (new JwtController())->generateToken($this->user->id)
        );

        return $request;
    }

    public function test_jwt_user_is_published_on_the_attributes_bag(): void
    {
        $request = $this->passThrough($this->authenticated());

        $this->assertSame($this->user->id, $request->attributes->get('jwt_user')['id']);
    }

    /**
     * The point of the phase. Previously this key was present in input()
     * and had to be defended by overwriting it.
     */
    public function test_jwt_user_is_absent_from_the_input_bag(): void
    {
        $request = $this->passThrough($this->authenticated());

        $this->assertNull($request->input('jwt_user'));
        $this->assertArrayNotHasKey('jwt_user', $request->all());
    }

    /**
     * A client can still put `jwt_user` in a request body, but it is now
     * inert: it is not where anything reads identity from, and it never
     * reaches the attributes bag.
     */
    public function test_client_supplied_jwt_user_cannot_reach_the_resolved_identity(): void
    {
        $this->authorisationUser(false);
        $nonAdmin = User::where('email', config(self::CFG_NONADMIN_EMAIL))->firstOrFail();

        $request = Request::create('/api/v1/users', 'GET', [
            'jwt_user' => ['id' => $this->user->id, 'is_admin' => 1],
        ]);
        $request->headers->set(
            'Authorization',
            'Bearer ' . (new JwtController())->generateToken($nonAdmin->id)
        );

        $resolved = $this->passThrough($request)->jwtUser();

        $this->assertSame($nonAdmin->id, $resolved['id']);
        $this->assertSame(0, (int) $resolved['is_admin']);
    }

    public function test_the_macro_reads_the_resolved_identity(): void
    {
        $request = $this->passThrough($this->authenticated());

        $this->assertSame($this->user->id, $request->jwtUser()['id']);
    }

    /**
     * Unauthenticated and integration requests must keep yielding an empty
     * array, because ~187 call sites rely on that falsiness and a further
     * ~116 turn it into ['id' => null] with `?:`.
     */
    public function test_the_macro_returns_an_empty_array_when_unauthenticated(): void
    {
        $request = Request::create('/api/v1/users', 'GET');

        $this->assertSame([], $request->jwtUser());
        $this->assertSame(['id' => null], $request->jwtUser() ?: ['id' => null]);
    }
}
