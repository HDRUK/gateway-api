<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Team;
use App\Models\Dataset;
use App\Models\TeamHasUser;
use App\Models\TeamHasDataAccessApplication;
use App\Http\Enums\TeamMemberOf;
use App\Jobs\SendEmailJob;
use App\Jobs\TermExtraction;
use App\Jobs\LinkageExtraction;
use Illuminate\Support\Facades\Queue;
use Tests\Traits\MockExternalApis;

/**
 * Verifies the fix for Vulns 1 & 2 from the Claude security baseline review:
 *   - Vuln 1: Cross-team IDOR on DAR applications (checkTeamAccess missing user membership check)
 *   - Vuln 2: DAR review data leakage across teams (same root cause)
 *
 * Each test documents the attack scenario and asserts the post-fix behaviour.
 */
class ClaudeSecurityReview_Sonnet_46_1 extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];
    protected $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        Team::flushEventListeners();

        Queue::fake([
            LinkageExtraction::class,
            TermExtraction::class,
            SendEmailJob::class,
        ]);

        $this->metadata = $this->getMetadata();
    }

    // -------------------------------------------------------------------------
    // Vuln 2: DAR Review read — user outside team must be rejected
    // -------------------------------------------------------------------------

    /**
     * A user who is NOT a member of the team that owns a DAR application must
     * receive 401 when they try to read that team's reviews, even if the
     * application ID is correct and the permission exists.
     *
     * Attack scenario (pre-fix): attacker in Team A calls
     *   GET /api/v1/teams/{teamB}/dar/applications/{id}/reviews
     * checkTeamAccess only verified the app belonged to teamB — it never checked
     * whether the caller was a member of teamB.
     */
    public function test_user_not_in_owning_team_cannot_read_dar_reviews(): void
    {
        // 1. Create Team A — the current admin user is added as a member by the
        //    TeamController when they create it.
        $teamAId = $this->createTeamViaApi();

        // 2. Create Team B directly through models so the current test user is
        //    deliberately NOT added as a member.
        $teamB = Team::factory()->create([
            'name' => 'Team B (no membership)',
            'enabled' => true,
        ]);

        // 3. Create a dataset owned by Team A, and a DAR application against it.
        $applicationId = $this->createDarApplication($teamAId);

        // 4. Link the same DAR application to Team B via the pivot table.
        //    This is what checkTeamAccess checks — it would have previously
        //    allowed access here (the app IS linked to Team B), without verifying
        //    that the requesting user is a member of Team B.
        TeamHasDataAccessApplication::firstOrCreate([
            'team_id' => $teamB->id,
            'dar_application_id' => $applicationId,
        ]);

        // 5. Current test user is NOT in Team B. Attempt to read reviews via
        //    Team B's URL — this is the IDOR exploit path.
        $response = $this->json(
            'GET',
            "api/v1/teams/{$teamB->id}/dar/applications/{$applicationId}/reviews",
            [],
            $this->header
        );

        // After the fix, this must be rejected.
        $response->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
    }

    /**
     * A user who IS a member of the owning team must be able to read reviews.
     * Confirms the fix does not break the legitimate access path.
     */
    public function test_user_in_owning_team_can_read_dar_reviews(): void
    {
        // Create a team via the API — the admin user is added as a member.
        $teamId = $this->createTeamViaApi();

        // Create a DAR application owned by this team.
        $applicationId = $this->createDarApplication($teamId);

        // Confirm membership (belt-and-braces assertion for test clarity).
        $this->assertTrue(
            TeamHasUser::where('team_id', $teamId)
                ->where('user_id', $this->currentUser['id'])
                ->exists(),
            'Setup error: test user should be a member of their own team'
        );

        // Access reviews as a member — should succeed.
        $response = $this->json(
            'GET',
            "api/v1/teams/{$teamId}/dar/applications/{$applicationId}/reviews",
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }

    // -------------------------------------------------------------------------
    // Vuln 1: DAR Application write — user outside team cannot modify
    // -------------------------------------------------------------------------

    /**
     * A user who is NOT a member of the team that owns a DAR application must
     * receive 401 when attempting to PATCH (edit) that application via the
     * team-scoped URL, even if the application ID is valid.
     *
     * Attack scenario (pre-fix): attacker in Team A calls
     *   PATCH /api/v1/teams/{teamB}/dar/applications/{id}
     * to alter approval_status on an application they have no business touching.
     */
    public function test_user_not_in_owning_team_cannot_edit_dar_application(): void
    {
        // Create Team A (current user is a member — this is the attacker's team).
        $teamAId = $this->createTeamViaApi();

        // Create Team B without adding the current user as a member.
        $teamB = Team::factory()->create([
            'name' => 'Team B (target)',
            'enabled' => true,
        ]);

        // Create a DAR application owned by Team A's dataset.
        $applicationId = $this->createDarApplication($teamAId);

        // Link the DAR application to Team B (simulates the provider side of a
        // multi-team DAR — this is what gave the IDOR its power pre-fix).
        TeamHasDataAccessApplication::firstOrCreate([
            'team_id' => $teamB->id,
            'dar_application_id' => $applicationId,
        ]);

        // Attempt to PATCH via Team B's URL while NOT a member of Team B.
        $response = $this->json(
            'PATCH',
            "api/v1/teams/{$teamB->id}/dar/applications/{$applicationId}",
            ['submission_status' => 'SUBMITTED'],
            $this->header
        );

        // After the fix, the request must be rejected.
        $response->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTeamViaApi(): int
    {
        $response = $this->json(
            'POST',
            'api/v1/teams',
            [
                'name' => 'Security Test Team ' . fake()->regexify('[A-Z]{5}'),
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => TeamMemberOf::HUB,
                'contact_point' => 'security-test@example.com',
                'application_form_updated_by' => 'Security Test',
                'application_form_updated_on' => '2024-01-01 00:00:00',
                'is_question_bank' => 1,
                'users' => [],
                'notifications' => [],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        return $response->decodeResponseJson()['data'];
    }

    private function createDarApplication(int $teamId): int
    {
        $metadata = $this->getMetadata();
        $team = Team::find($teamId);
        $metadata['metadata']['summary']['publisher'] = [
            'name' => $team->name,
            'gatewayId' => $team->id,
        ];

        $datasetResponse = $this->json(
            'POST',
            'api/v1/datasets',
            [
                'team_id' => $teamId,
                'user_id' => $this->currentUser['id'],
                'metadata' => $metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header
        );
        $datasetResponse->assertStatus(201);
        $datasetId = $datasetResponse['data'];

        $applicationResponse = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => $this->currentUser['id'],
                'project_title' => 'Security Review DAR',
                'dataset_ids' => [$datasetId],
            ],
            $this->header
        );
        $applicationResponse->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return $applicationResponse->decodeResponseJson()['data'];
    }
}
