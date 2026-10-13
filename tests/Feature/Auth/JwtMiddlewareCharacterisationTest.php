<?php

namespace Tests\Feature\Auth;

use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\JwtController;
use App\Http\Middleware\JwtMiddleware;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Characterisation tests for JwtMiddleware, which guards 430 of the 594
 * registered routes and is the de-facto authentication layer.
 *
 * The middleware is exercised directly rather than through a route: its
 * contract is "what does the next closure receive", and every consumer
 * downstream reads that out of the request INPUT bag rather than from
 * Auth::user(). Pinning the shape here is what makes it safe to move
 * that state onto a guard without touching 74 additional call sites.
 *
 * Note the three mutually exclusive credential branches, checked in this
 * order: token cookie, then application headers, then bearer header.
 *
 * @see \App\Http\Middleware\JwtMiddleware
 */
class JwtMiddlewareCharacterisationTest extends TestCase
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

    /**
     * Run the middleware and return whatever the next closure saw on the
     * request, so the tests assert on the middleware's actual output
     * rather than on a controller's interpretation of it.
     */
    private function capture(Request $request): array
    {
        $seen = [];

        (new JwtMiddleware())->handle($request, function (Request $passed) use (&$seen) {
            $seen = [
                'jwt_user' => $passed->input('jwt_user'),
                'app' => $passed->input('app'),
                'jwt' => $passed->input('jwt'),
            ];

            return response()->json(['ok' => true]);
        });

        return $seen;
    }

    private function tokenFor(User $user): string
    {
        return (new JwtController())->generateToken($user->id);
    }

    private function request(array $body = []): Request
    {
        return Request::create('/api/v1/users', 'GET', $body);
    }

    public function test_bearer_header_populates_jwt_user(): void
    {
        $token = $this->tokenFor($this->user);

        $request = $this->request();
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $seen = $this->capture($request);

        $this->assertSame($this->user->id, $seen['jwt_user']['id']);
        $this->assertSame($token, $seen['jwt'], 'The raw token is also merged in as `jwt`.');
    }

    public function test_token_cookie_populates_jwt_user(): void
    {
        $token = $this->tokenFor($this->user);

        $request = $this->request();
        $request->cookies->set('token', $token);

        $seen = $this->capture($request);

        $this->assertSame($this->user->id, $seen['jwt_user']['id']);
        $this->assertSame($token, $seen['jwt']);
    }

    /**
     * The cookie branch is checked first, so a cookie wins over a bearer
     * header when both are present. Any replacement must keep this
     * precedence or the social flow (which sets the cookie) will
     * start behaving differently for clients that send both.
     */
    public function test_cookie_takes_precedence_over_bearer_header(): void
    {
        $this->authorisationUser(false);
        $other = User::where('email', config(self::CFG_NONADMIN_EMAIL))->firstOrFail();

        $request = $this->request();
        $request->cookies->set('token', $this->tokenFor($other));
        $request->headers->set('Authorization', 'Bearer ' . $this->tokenFor($this->user));

        $seen = $this->capture($request);

        $this->assertSame($other->id, $seen['jwt_user']['id']);
    }

    /**
     * The jwt_user payload is deliberately narrow — four scalars plus the
     * resolved role/permission tree. Note that role_perms is re-computed
     * from the database on EVERY request, so it is authoritative, while
     * the `user` claim inside the token itself is a stale snapshot.
     */
    public function test_jwt_user_payload_shape(): void
    {
        $request = $this->request();
        $request->headers->set('Authorization', 'Bearer ' . $this->tokenFor($this->user));

        $jwtUser = $this->capture($request)['jwt_user'];

        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'email', 'is_admin', 'role_perms'],
            array_keys($jwtUser),
        );

        $this->assertEqualsCanonicalizing(
            ['cohort', 'extra', 'summary'],
            array_keys($jwtUser['role_perms']),
            'A user in no teams has no `teams` key; CheckAccessMiddleware guards for this with isset().',
        );
    }

    /**
     * Auth state is merged into the same input bag the client
     * controls, so this asserts the merge order actually wins.
     *
     * Today this is safe only because every check.access route also has
     * jwt.verify in front of it. Moving to a guard removes the reliance
     * on that route config.
     */
    public function test_client_supplied_jwt_user_is_overwritten_not_trusted(): void
    {
        $this->authorisationUser(false);
        $nonAdmin = User::where('email', config(self::CFG_NONADMIN_EMAIL))->firstOrFail();

        $request = $this->request([
            'jwt_user' => ['id' => $this->user->id, 'is_admin' => 1],
        ]);
        $request->headers->set('Authorization', 'Bearer ' . $this->tokenFor($nonAdmin));

        $jwtUser = $this->capture($request)['jwt_user'];

        $this->assertSame($nonAdmin->id, $jwtUser['id']);
        $this->assertSame(0, (int) $jwtUser['is_admin']);
    }

    public function test_application_credentials_populate_app_and_skip_jwt_user(): void
    {
        $appId = 'app-' . fake()->regexify('[A-Za-z0-9]{20}');
        $clientId = 'client-' . fake()->regexify('[A-Za-z0-9]{20}');

        $application = Application::create([
            'name' => 'Characterisation App',
            'app_id' => $appId,
            'client_id' => $clientId,
            'client_secret' => Hash::make(
                $appId . ':' . $clientId . ':' . config('auth.private_salt') . ':' . config('auth.private_salt_2')
            ),
            'description' => 'Fixture for middleware characterisation.',
            'team_id' => 1,
            'user_id' => $this->user->id,
            'enabled' => 1,
        ]);

        $request = $this->request();
        $request->headers->set('x-application-id', $appId);
        $request->headers->set('x-client-id', $clientId);

        $seen = $this->capture($request);

        $this->assertNull($seen['jwt_user'], 'Integration requests never get a jwt_user.');

        // The application's PK, not the public app_id from the
        // header. This previously merged `(int) $appId`, which collapsed a
        // 40-character random string to 0 and broke every consumer that
        // looked the application back up. See AuthRegressionTest.
        $this->assertSame(['id' => $application->id], $seen['app']);
    }

    public function test_unknown_application_id_is_rejected(): void
    {
        $request = $this->request();
        $request->headers->set('x-application-id', 'does-not-exist');
        $request->headers->set('x-client-id', 'nor-this');

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('No known integration matches the credentials provided');

        $this->capture($request);
    }

    public function test_request_without_any_credentials_is_rejected(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->capture($this->request());
    }

    public function test_token_signed_with_the_wrong_key_is_rejected(): void
    {
        $request = $this->request();
        $request->headers->set('Authorization', 'Bearer not-a-real-token');

        $this->expectException(UnauthorizedException::class);

        $this->capture($request);
    }

    /**
     * Only the `bearer` scheme is handled; anything else falls through to
     * the catch-all rejection at the end of the middleware.
     */
    public function test_non_bearer_authorization_scheme_is_rejected(): void
    {
        $request = $this->request();
        $request->headers->set('Authorization', 'Basic ' . base64_encode('user:pass'));

        $this->expectException(UnauthorizedException::class);

        $this->capture($request);
    }

    /**
     * A token outlives the user it was minted for. Because User::find()
     * respects the SoftDeletes scope, a deleted user's still-valid token
     * is rejected — but as a 404, not a 401.
     */
    public function test_valid_token_for_a_soft_deleted_user_raises_not_found(): void
    {
        $token = $this->tokenFor($this->user);
        $this->user->delete();

        $request = $this->request();
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $this->expectException(NotFoundException::class);

        $this->capture($request);
    }
}
