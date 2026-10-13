<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Executable evidence for defects found while assessing the auth
 * layer ahead of more solid improvements.
 *
 * Every test here asserts behaviour that is WRONG. They exist so the
 * issues are reproducible, tracked, and impossible to lose and so
 * that the phase which fixes each one has a test that flips from green
 * to red, forcing a deliberate update rather than a silent change.
 *
 * We should not "fix" a failure here by relaxing the assertion. A failure means
 * the defect is gone: invert the test to assert the correct behaviour instead.
 *
 * Once inverted, a test moves to AuthRegressionTest, which is where the
 * already-fixed defects now live.
 *
 * @see \Tests\Feature\Auth\AuthRegressionTest
 */
class AuthKnownDefectsTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function authHeaders(): array
    {
        $this->authorisationUser();

        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getAuthorisationJwt(),
        ];
    }

    /**
     * Issue: Logging out does not invalidate the token.
     *
     * LogoutController flushes the session, but the JWT flow never uses
     * one — authentication is entirely bearer/cookie driven. The token
     * therefore stays valid for the remainder of jwt.expiration (24h by
     * defaul) after the user has explicitly signed out. There is no
     * denylist and no jti to revoke against, so there is currently no way
     * to invalidate an issued token at all, including after a compromise.
     *
     * Fixed by: the revocation phase (jti denylist + real logout).
     */
    public function test_defect_logout_does_not_invalidate_the_token(): void
    {
        $headers = $this->authHeaders();

        $this->json('POST', '/api/v1/logout', [], $headers)->assertStatus(200);

        // The same bearer token is still accepted after logout.
        $this->json('GET', '/api/v1/users', [], $headers)->assertStatus(200);
    }
}
