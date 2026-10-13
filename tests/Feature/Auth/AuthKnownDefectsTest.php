<?php

namespace Tests\Feature\Auth;

use App\Models\Application;
use App\Models\ApplicationHasPermission;
use App\Models\Permission;
use App\Models\Team;
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

    /**
     * Issue: The refresh endpoint is entirely non-functional.
     *
     * AuthController::refreshToken() calls
     *     User::where(['id', $userId])
     * where it means
     *     User::where('id', $userId)
     *
     * The malformed where matches nothing, so a perfectly valid
     * token yields "Unauthorized" which is surfaced as a 500 rather than a 401,
     * because the UnauthorizedException is caught by the generic
     * `catch (Exception $e)` and rethrown as a bare Exception.
     *
     * Fixed by: the one line where correction (quick win).
     */
    public function test_defect_refresh_token_endpoint_always_fails(): void
    {
        $response = $this->json('POST', '/api/v1/refresh_token', [], $this->authHeaders());

        $response->assertStatus(500);
        $this->assertSame('Unauthorized', $response->json('message'));
    }

    /**
     * Issue: The integration application id is cast to int and lost.
     *
     * JwtMiddleware merges `['app' => ['id' => (int) $appId]]`, but
     * `applications.app_id` is a 40-character random string
     * (Str::random(40)). The cast yields 0 for every realistic value.
     *
     * Two independent errors compound here: even with the cast removed,
     * the consumers query the `id` PK with what is actually an
     * `app_id` value.
     *
     * The blast radius is any integration endpoint that attributes a
     * write to the calling application rather than to a user id supplied
     * in the body. IntegrationDurController::store() is one: it falls
     * through to the application branch, looks up Application id 0, gets
     * null, and dies dereferencing it.
     *
     * This is invisible to the existing suite because every integration
     * test passes an explicit `user_id` in the request body, which takes
     * the first branch and never reaches the broken one.
     *
     * Fixed by: correcting the lookup to `where('app_id', $appId)` and
     * dropping the int cast.
     */
    public function test_defect_integration_app_id_is_cast_to_zero(): void
    {
        $application = Application::where('id', 1)->firstOrFail();

        $this->assertSame(
            0,
            (int) $application->app_id,
            'app_id is a random 40-char string, so the middleware cast collapses it to 0.',
        );
    }

    public function test_defect_integration_write_without_explicit_user_id_is_a_500(): void
    {
        $application = Application::where('id', 1)->firstOrFail();

        foreach (Permission::whereIn('name', ['dur.create'])->get() as $permission) {
            ApplicationHasPermission::firstOrCreate([
                'application_id' => $application->id,
                'permission_id' => $permission->id,
            ]);
        }

        $headers = array_merge($this->header, [
            'x-application-id' => $application->app_id,
            'x-client-id' => $application->client_id,
        ]);

        // Deliberately omits `user_id`, so attribution falls through to
        // the application branch — the path no existing test covers.
        $response = $this->json('POST', '/api/v1/integrations/dur', [
            'team_id' => (int) Team::all()->random()->id,
            'non_gateway_datasets' => ['External Dataset 01'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'academia',
            'status' => 'ACTIVE',
        ], $headers);

        $response->assertStatus(500);
        $this->assertSame(
            'Attempt to read property "user_id" on null',
            $response->json('message'),
        );
    }
}
