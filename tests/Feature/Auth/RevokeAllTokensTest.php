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
 * Admin kill-switch: withdraw every token issued so far, in one action.
 *
 * Tokens are stateless and we never record them at issue, so there is no
 * list of live tokens to walk. Instead a single cutoff is recorded and any
 * token whose `iat` predates it is refused. Tokens minted afterwards (i.e.
 * everyone signing back in) are unaffected.
 *
 * The cutoff is held for one token lifetime: by then every token it could
 * apply to has expired on its own.
 *
 * @see \App\Auth\TokenDenylist::revokeAllIssuedBefore()
 */
class RevokeAllTokensTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const REVOKE_ALL_URL = '/api/v2/admin/tokens/revoke_all';

    private User $admin;
    private User $other;

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->authorisationUser();
        $this->admin = User::where('email', config(self::CFG_TEST_EMAIL))->firstOrFail();

        $this->authorisationUser(false);
        $this->other = User::where('email', config(self::CFG_NONADMIN_EMAIL))->firstOrFail();
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

    public function test_revoke_all_withdraws_every_existing_token(): void
    {
        $adminToken = $this->tokenFor($this->admin);
        $otherToken = $this->tokenFor($this->other);

        $this->json('GET', '/api/v1/users', [], $this->headersFor($otherToken))->assertStatus(200);

        $this->json('POST', self::REVOKE_ALL_URL, [], $this->headersFor($adminToken))
            ->assertStatus(200);

        $this->json('GET', '/api/v1/users', [], $this->headersFor($otherToken))->assertStatus(401);
    }

    /**
     * Deliberate: the caller is not exempt. If the action is being taken
     * because of a compromise, the admin's own token is as suspect as
     * anyone's. The UI warns before confirming.
     */
    public function test_revoke_all_withdraws_the_callers_own_token(): void
    {
        $adminToken = $this->tokenFor($this->admin);

        $this->json('POST', self::REVOKE_ALL_URL, [], $this->headersFor($adminToken))
            ->assertStatus(200);

        $this->json('GET', '/api/v1/users', [], $this->headersFor($adminToken))->assertStatus(401);
    }

    public function test_tokens_issued_after_revoke_all_are_accepted(): void
    {
        $this->json('POST', self::REVOKE_ALL_URL, [], $this->headersFor($this->tokenFor($this->admin)))
            ->assertStatus(200);

        $fresh = $this->tokenFor($this->other);

        $this->json('GET', '/api/v1/users', [], $this->headersFor($fresh))->assertStatus(200);
    }

    public function test_revoke_all_is_refused_for_non_superadmins(): void
    {
        $otherToken = $this->tokenFor($this->other);
        $adminToken = $this->tokenFor($this->admin);

        $response = $this->json('POST', self::REVOKE_ALL_URL, [], $this->headersFor($otherToken));
        $this->assertContains($response->status(), [401, 403]);

        // And nothing was revoked as a side effect.
        $this->json('GET', '/api/v1/users', [], $this->headersFor($adminToken))->assertStatus(200);
    }

    public function test_revoke_all_requires_authentication(): void
    {
        $this->json('POST', self::REVOKE_ALL_URL, [], ['Accept' => 'application/json'])
            ->assertStatus(401);
    }

    /**
     * The guard and the middleware must agree, as with per-token
     * revocation, or $request->user() would see a user the middleware
     * has already turned away.
     */
    public function test_the_guard_refuses_a_token_issued_before_the_cutoff(): void
    {
        $token = $this->tokenFor($this->other);

        $request = Request::create('/api/v1/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $resolver = app(JwtUserResolver::class);
        $this->assertSame($this->other->id, $resolver->resolve($request)?->id);

        app(TokenDenylist::class)->revokeAllIssuedBefore(CarbonImmutable::now());

        $this->assertNull($resolver->resolve($request));
    }

    /**
     * After one token lifetime every token the cutoff could apply to has
     * expired anyway, so it is allowed to lapse rather than sit in the
     * cache forever.
     */
    public function test_the_cutoff_lapses_after_one_token_lifetime(): void
    {
        $denylist = app(TokenDenylist::class);
        $cutoff = CarbonImmutable::now();

        $denylist->revokeAllIssuedBefore($cutoff);

        $stale = ['jti' => 'stale', 'iat' => $cutoff->subSecond()];
        $this->assertTrue($denylist->rejects($stale));

        $this->travelTo($cutoff->addSeconds((int) config('jwt.expiration'))->addMinute());

        $this->assertFalse($denylist->rejects($stale));
    }

    /**
     * Per-token revocation from logout must keep working alongside the
     * cutoff: the two are independent reasons to refuse a token.
     */
    public function test_per_token_revocation_still_applies_after_the_cutoff(): void
    {
        $this->json('POST', self::REVOKE_ALL_URL, [], $this->headersFor($this->tokenFor($this->admin)))
            ->assertStatus(200);

        $fresh = $this->tokenFor($this->other);
        $headers = $this->headersFor($fresh);

        $this->json('POST', '/api/v1/logout', [], $headers)->assertStatus(200);

        $this->json('GET', '/api/v1/users', [], $headers)->assertStatus(401);
    }
}
