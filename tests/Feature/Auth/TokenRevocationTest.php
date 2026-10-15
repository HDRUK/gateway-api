<?php

namespace Tests\Feature\Auth;

use App\Auth\JwtUserResolver;
use App\Auth\TokenDenylist;
use App\Http\Controllers\JwtController;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Phase 3 of the auth migration: issued tokens can now be revoked.
 *
 * Until this landed there was no way to invalidate a token at all. Logout
 * flushed a session the JWT flow never used, so a signed-out user's
 * bearer token kept working for the rest of its 24-hour life, and a
 * leaked token could not be withdrawn.
 *
 * Revocation is a denylist keyed on the token's `jti`, held in the cache
 * with a TTL equal to the token's own remaining lifetime - so entries
 * expire exactly when they stop mattering and the list cannot grow
 * without bound.
 *
 * @see \App\Auth\TokenDenylist
 */
class TokenRevocationTest extends TestCase
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

    private function headersFor(string $token): array
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    private function tokenFor(User $user): string
    {
        return (new JwtController())->generateToken($user->id);
    }

    private function claims(string $token): array
    {
        $jwt = new JwtController();
        $jwt->setJwt($token);

        return $jwt->decode();
    }

    public function test_logout_revokes_the_presented_token(): void
    {
        $token = $this->tokenFor($this->user);
        $headers = $this->headersFor($token);

        // Valid before logout.
        $this->json('GET', '/api/v1/users', [], $headers)->assertStatus(200);

        $this->json('POST', '/api/v1/logout', [], $headers)->assertStatus(200);

        // And refused afterwards. This is the behaviour the whole phase exists for.
        $this->json('GET', '/api/v1/users', [], $headers)->assertStatus(401);
    }

    public function test_logout_does_not_affect_other_tokens(): void
    {
        $this->authorisationUser(false);
        $other = User::where('email', config(self::CFG_NONADMIN_EMAIL))->firstOrFail();

        $mine = $this->tokenFor($this->user);
        $theirs = $this->tokenFor($other);

        $this->json('POST', '/api/v1/logout', [], $this->headersFor($mine))->assertStatus(200);

        $this->json('GET', '/api/v1/users', [], $this->headersFor($theirs))->assertStatus(200);
    }

    /**
     * The same user signing in on two devices holds two tokens with
     * different jti values; signing out of one must not sign out the other.
     */
    public function test_revocation_is_per_token_not_per_user(): void
    {
        $phone = $this->tokenFor($this->user);
        $laptop = $this->tokenFor($this->user);

        $this->json('POST', '/api/v1/logout', [], $this->headersFor($phone))->assertStatus(200);

        $this->json('GET', '/api/v1/users', [], $this->headersFor($laptop))->assertStatus(200);
    }

    /**
     * The guard and the middleware must agree about a revoked token, or
     * code migrated to $request->user() would see a user the middleware
     * has already rejected.
     */
    public function test_the_guard_refuses_a_revoked_token(): void
    {
        $token = $this->tokenFor($this->user);

        $request = Request::create('/api/v1/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $resolver = app(JwtUserResolver::class);
        $this->assertSame($this->user->id, $resolver->resolve($request)?->id);

        app(TokenDenylist::class)->revoke(
            $this->claims($token)['jti'],
            $this->claims($token)['exp'],
        );

        $this->assertNull($resolver->resolve($request));
    }

    /**
     * Entries are bounded by the token's own expiry, so the denylist
     * cannot grow without limit. Once the token would have expired on its
     * own, carrying it on the list is pointless.
     */
    public function test_denylist_entries_expire_with_the_token(): void
    {
        $token = $this->tokenFor($this->user);
        $claims = $this->claims($token);

        $denylist = app(TokenDenylist::class);
        $denylist->revoke($claims['jti'], $claims['exp']);

        $this->assertTrue($denylist->isRevoked($claims['jti']));

        $this->travelTo(CarbonImmutable::instance($claims['exp'])->addMinute());

        $this->assertFalse(
            $denylist->isRevoked($claims['jti']),
            'The entry should have aged out once the token expired anyway.',
        );
    }

    public function test_revoking_an_already_expired_token_stores_nothing(): void
    {
        $denylist = app(TokenDenylist::class);

        $denylist->revoke('expired-token-jti', CarbonImmutable::now()->subHour());

        $this->assertFalse($denylist->isRevoked('expired-token-jti'));
    }

    /**
     * The social-login flow authenticates by setting a `token` cookie, so
     * logout has to clear it as well as revoking the token. Otherwise the
     * browser keeps presenting a withdrawn token and the user sees 401s
     * instead of a clean signed-out state.
     *
     * Cookie::forget() inherits the same path and domain defaults that
     * Cookie::make() used when the cookie was set, so this matches in
     * every environment without hardcoding a domain - which matters here,
     * since web and api sit on sibling subdomains that differ per
     * environment.
     *
     * withCredentials() is required: json() mirrors browser XHR and sends
     * no cookies without it, so the request would otherwise arrive
     * unauthenticated.
     */
    public function test_logout_clears_the_social_login_cookie(): void
    {
        $response = $this->withCredentials()
            ->withUnencryptedCookie('token', $this->tokenFor($this->user))
            ->json('POST', '/api/v1/logout', [], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertCookieExpired('token');
    }

    /**
     * Logging out through the cookie path must revoke the token too, not
     * merely drop the cookie - otherwise a copy of the token taken before
     * logout would still work as a bearer credential.
     */
    public function test_logout_via_cookie_revokes_the_token_as_well(): void
    {
        $token = $this->tokenFor($this->user);

        $this->withCredentials()
            ->withUnencryptedCookie('token', $token)
            ->json('POST', '/api/v1/logout', [], ['Accept' => 'application/json'])
            ->assertStatus(200);

        $this->json('GET', '/api/v1/users', [], $this->headersFor($token))->assertStatus(401);
    }

    /**
     * Revocation is only as sound as jti uniqueness: two tokens sharing a
     * jti would mean revoking one revokes the other.
     *
     * jti was md5(microtime()), which collides for tokens minted within
     * the same microsecond - plausible across Octane workers under load.
     * It is now cryptographically random, keeping the same 32-hex shape.
     */
    public function test_jti_values_do_not_collide_under_rapid_minting(): void
    {
        $jtis = [];

        for ($i = 0; $i < 250; $i++) {
            $jtis[] = $this->claims($this->tokenFor($this->user))['jti'];
        }

        $this->assertCount(250, array_unique($jtis), 'jti values must be unique per token.');
    }
}
