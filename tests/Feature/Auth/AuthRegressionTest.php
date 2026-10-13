<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\JwtController;
use App\Http\Middleware\JwtMiddleware;
use App\Models\Application;
use App\Models\ApplicationHasPermission;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\DurHasDatasetVersion;
use App\Models\Permission;
use App\Models\Team;
use Illuminate\Http\Request;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Regression guards for auth defects that have been fixed.
 *
 * Each of these started life in AuthKnownDefectsTest asserting the broken
 * behaviour, and was inverted when the fix landed. They assert the
 * CORRECT behaviour now, so a regression fails here rather than silently
 * reappearing in production.
 *
 * AuthKnownDefectsTest itself is gone: every defect it recorded has been
 * fixed, and an empty test class is an error rather than a milestone. Its
 * last entry - logout not invalidating the token - is now inverted in
 * TokenRevocationTest. Reinstate the file if a new defect needs parking.
 *
 * @see \Tests\Feature\Auth\TokenRevocationTest
 */
class AuthRegressionTest extends TestCase
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

    private function integrationHeaders(Application $application): array
    {
        foreach (Permission::whereIn('name', ['dur.create'])->get() as $permission) {
            ApplicationHasPermission::firstOrCreate([
                'application_id' => $application->id,
                'permission_id' => $permission->id,
            ]);
        }

        return array_merge($this->header, [
            'x-application-id' => $application->app_id,
            'x-client-id' => $application->client_id,
        ]);
    }

    /**
     * Was: a malformed where-array (`where(['id', $userId])`) meant the
     * endpoint rejected every token it was given, surfacing as a 500.
     */
    public function test_refresh_token_returns_a_usable_token(): void
    {
        $response = $this->json('POST', '/api/v1/refresh_token', [], $this->authHeaders());

        $response->assertStatus(200);
        $response->assertJsonStructure(['access_token', 'token_type']);

        // The returned token must actually authenticate a subsequent call.
        $this->json('GET', '/api/v1/users', [], [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $response->json('access_token'),
        ])->assertStatus(200);
    }

    /**
     * Was: JwtMiddleware merged `(int) $appId`, casting a 40-character
     * random app_id to 0. It now passes the application's primary key,
     * which is what every consumer looks the application up by.
     */
    public function test_middleware_passes_the_application_primary_key(): void
    {
        $application = Application::where('id', 1)->firstOrFail();

        $request = Request::create('/api/v1/integrations/dur', 'GET');
        $request->headers->set('x-application-id', $application->app_id);
        $request->headers->set('x-client-id', $application->client_id);

        $seen = null;
        (new JwtMiddleware())->handle($request, function (Request $passed) use (&$seen) {
            $seen = $passed->input('app');

            return response()->json(['ok' => true]);
        });

        $this->assertSame(['id' => $application->id], $seen);
        $this->assertNotSame(0, $seen['id'], 'A zero here means the int cast is back.');
    }

    /**
     * Was: an integration write that relied on the calling application for
     * attribution hit `Application::where(['id' => 0])`, got null, and died
     * dereferencing it. The whole suite missed it because every other test
     * passes an explicit user_id and takes a different branch.
     *
     * Note `application_id` is recorded on the dur_has_dataset_version
     * pivot, not on durs, so a dataset is required to exercise it. Before
     * the fix the id was 0, which is falsy, so the pivot column was simply
     * never written - attribution was lost silently rather than wrongly.
     */
    public function test_integration_write_without_user_id_attributes_to_the_application(): void
    {
        $application = Application::where('id', 1)->firstOrFail();
        $teamId = (int) Team::all()->random()->id;

        // Deliberately omits `user_id` so attribution falls through to the
        // application branch.
        $response = $this->json('POST', '/api/v1/integrations/dur', [
            'team_id' => $teamId,
            'datasets' => [['id' => Dataset::all()->random()->id, 'reason' => 'characterisation']],
            'non_gateway_datasets' => ['External Dataset 01'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'academia',
            'status' => 'ACTIVE',
        ], $this->integrationHeaders($application));

        $response->assertStatus(201);

        $dur = Dur::where('id', $response->json('data'))->firstOrFail();

        $this->assertSame(
            (int) $application->user_id,
            (int) $dur->user_id,
            "Absent an explicit user_id, the application's owner is used.",
        );

        $pivot = DurHasDatasetVersion::where('dur_id', $dur->id)->firstOrFail();

        $this->assertSame(
            $application->id,
            (int) $pivot->application_id,
            'The dataset link should be attributed to the calling integration.',
        );
    }

    /**
     * Was: config/jwt.php defaulted JWT_SECRET to 300, so an unset secret
     * silently signed every token with the string "300".
     */
    public function test_a_missing_jwt_secret_is_refused_rather_than_defaulted(): void
    {
        config(['jwt.secret' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT_SECRET is not set');

        new JwtController();
    }
}
