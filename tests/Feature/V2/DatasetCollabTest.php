<?php

namespace Tests\Feature\V2;

use App\Http\Enums\TeamMemberOf;
use App\Models\Dataset;
use App\Models\DatasetShareToken;
use App\Models\DatasetVersion;
use Config;
use Laravel\Pennant\Feature;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class DatasetCollabTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_TEAM = '/api/v1/teams';

    public const TEST_URL_NOTIFICATION = '/api/v1/notifications';

    protected $metadata;

    protected $nonAdminJwt;

    protected $nonAdminUser;

    protected $headerNonAdmin;

    protected function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->metadata = $this->getMetadata();

        // Flush the Pennant in-memory cache, then write an explicit stored
        // value so Feature::active() always sees a fresh result within each
        // test - the flag defaults to inactive, and this whole controller is
        // unreachable without it.
        Feature::flushCache();
        Feature::activate('CollaborativeMetadataOnboarding');

        $this->authorisationUser(false);
        $this->nonAdminJwt = $this->getAuthorisationJwt(false);
        $this->nonAdminUser = $this->getUserFromJwt($this->nonAdminJwt);
        $this->headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->nonAdminJwt,
        ];
    }

    private function collabUrl(string $token): string
    {
        return 'api/v2/collab/'.$token;
    }

    /**
     * Mints a share token via the authenticated admin endpoint and returns
     * [token, datasetId, teamId].
     */
    private function createShareToken(): array
    {
        $notificationId = $this->createNotification();
        $teamId = $this->createTeam([$this->nonAdminUser['id']], [$notificationId]);

        $response = $this->json(
            'POST',
            'api/v2/teams/'.$teamId.'/datasets/share-tokens',
            [],
            $this->headerNonAdmin,
        );
        $data = $response->decodeResponseJson()['data'];

        return [$data['token'], $data['dataset_id'], $teamId];
    }

    public function test_public_show_returns_draft_metadata_for_valid_token_without_auth(): void
    {
        [$token, $datasetId, $teamId] = $this->createShareToken();
        $team = \App\Models\Team::where('id', $teamId)->first();

        $response = $this->json('GET', $this->collabUrl($token), [], ['Accept' => 'application/json']);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $data = $response->decodeResponseJson()['data'];
        $this->assertEquals($datasetId, $data['dataset_id']);
        $this->assertArrayHasKey('team_id', $data);
        $this->assertArrayHasKey('schema', $data);
        // Anonymous collaborators can't call the authenticated GET
        // /teams/{id} endpoint - the team's contact_point must be exposed
        // here instead, so the web app has a fallback for the hidden
        // required "contact point" fields.
        $this->assertEquals($team->contact_point, $data['team_contact_point']);
    }

    public function test_public_show_returns_404_for_unknown_token(): void
    {
        $response = $this->json('GET', $this->collabUrl('does-not-exist'), [], ['Accept' => 'application/json']);
        $response->assertStatus(404);
    }

    public function test_public_show_returns_404_for_expired_token(): void
    {
        [$token] = $this->createShareToken();
        DatasetShareToken::where('token', $token)->update(['expires_at' => now()->subDay()]);

        $response = $this->json('GET', $this->collabUrl($token), [], ['Accept' => 'application/json']);
        $response->assertStatus(404);
    }

    public function test_public_show_returns_404_for_revoked_token(): void
    {
        [$token] = $this->createShareToken();
        DatasetShareToken::where('token', $token)->update(['revoked_at' => now()]);

        $response = $this->json('GET', $this->collabUrl($token), [], ['Accept' => 'application/json']);
        $response->assertStatus(404);
    }

    /**
     * The whole controller is gated behind CollaborativeMetadataOnboarding -
     * with it off, even a perfectly valid, unexpired, unrevoked token must
     * be treated exactly like an unknown one (same 404, same message),
     * since this is a public endpoint with nothing else to gate access.
     */
    public function test_public_show_returns_404_when_feature_flag_disabled(): void
    {
        [$token] = $this->createShareToken();
        Feature::deactivate('CollaborativeMetadataOnboarding');

        $response = $this->json('GET', $this->collabUrl($token), [], ['Accept' => 'application/json']);
        $response->assertStatus(404);
    }

    public function test_public_update_saves_draft_progress_without_auth(): void
    {
        [$token, $datasetId] = $this->createShareToken();

        $response = $this->json(
            'PUT',
            $this->collabUrl($token),
            ['metadata' => $this->metadata],
            ['Accept' => 'application/json'],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $version = DatasetVersion::where('dataset_id', $datasetId)->first();
        $this->assertEquals($this->metadata['metadata']['summary']['title'], $version->title);

        $token = DatasetShareToken::where('token', $token)->first();
        $this->assertNotNull($token->last_submitted_at);
    }

    /**
     * Regression test: saving a draft must not lose (or let the collaborator
     * override) the backend-owned fields - version/issued/modified/revisions
     * and the data custodian - even though the collaborator's own submitted
     * metadata never includes them (the custodian picker is disabled/no-op
     * in collab mode, and these fields aren't part of the visible form).
     */
    public function test_public_update_seeds_backend_owned_fields_even_when_submission_omits_them(): void
    {
        [$token, $datasetId] = $this->createShareToken();
        $team = \App\Models\Team::where('id', DatasetShareToken::where('token', $token)->first()->team_id)->first();

        $submitted = $this->metadata;
        unset($submitted['metadata']['summary']['dataCustodian']);

        $response = $this->json(
            'PUT',
            $this->collabUrl($token),
            ['metadata' => $submitted],
            ['Accept' => 'application/json'],
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $dataset = Dataset::where('id', $datasetId)->first();
        $wrapper = $dataset->lastMetadata();

        foreach (['metadata', 'original_metadata'] as $key) {
            $this->assertNotNull($wrapper[$key]['issued'] ?? null);
            $this->assertNotNull($wrapper[$key]['modified'] ?? null);
            $this->assertEquals(
                $team->pid,
                $wrapper[$key]['summary']['dataCustodian']['identifier'] ?? null,
            );
        }
    }

    /**
     * `issued` must be stable across saves (it's the dataset's creation
     * date), while `modified` must advance on every save.
     */
    public function test_public_update_preserves_issued_and_advances_modified_across_saves(): void
    {
        [$token, $datasetId] = $this->createShareToken();

        $this->json('PUT', $this->collabUrl($token), ['metadata' => $this->metadata], ['Accept' => 'application/json']);
        $dataset = Dataset::where('id', $datasetId)->first();
        $firstSave = $dataset->lastMetadata();

        sleep(1);

        $this->json('PUT', $this->collabUrl($token), ['metadata' => $this->metadata], ['Accept' => 'application/json']);
        $dataset->refresh();
        $secondSave = $dataset->lastMetadata();

        $this->assertEquals(
            $firstSave['original_metadata']['issued'],
            $secondSave['original_metadata']['issued'],
        );
        $this->assertNotEquals(
            $firstSave['original_metadata']['modified'],
            $secondSave['original_metadata']['modified'],
        );
    }

    /**
     * The collab PUT endpoint must never promote the draft to ACTIVE - only
     * the team admin can do that, via the normal authenticated flow, once
     * they've reviewed the submission.
     */
    public function test_public_update_never_activates_the_dataset(): void
    {
        [$token, $datasetId] = $this->createShareToken();

        $this->json(
            'PUT',
            $this->collabUrl($token),
            ['metadata' => $this->metadata],
            ['Accept' => 'application/json'],
        );

        $dataset = Dataset::where('id', $datasetId)->first();
        $this->assertEquals(Dataset::STATUS_DRAFT, $dataset->status);
    }

    public function test_public_can_mark_draft_ready_for_review(): void
    {
        [$token] = $this->createShareToken();

        $response = $this->json(
            'PATCH',
            $this->collabUrl($token).'/ready',
            [],
            ['Accept' => 'application/json'],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertNotNull($response->decodeResponseJson()['data']['ready_for_review_at']);

        $shareToken = DatasetShareToken::where('token', $token)->first();
        $this->assertNotNull($shareToken->ready_for_review_at);
    }

    public function test_public_can_unmark_draft_ready_for_review(): void
    {
        [$token] = $this->createShareToken();

        $this->json('PATCH', $this->collabUrl($token).'/ready', [], ['Accept' => 'application/json']);
        $response = $this->json(
            'PATCH',
            $this->collabUrl($token).'/ready',
            ['ready' => false],
            ['Accept' => 'application/json'],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertNull($response->decodeResponseJson()['data']['ready_for_review_at']);
    }

    public function test_ready_for_review_does_not_change_dataset_status(): void
    {
        [$token, $datasetId] = $this->createShareToken();

        $this->json('PATCH', $this->collabUrl($token).'/ready', [], ['Accept' => 'application/json']);

        $dataset = Dataset::where('id', $datasetId)->first();
        $this->assertEquals(Dataset::STATUS_DRAFT, $dataset->status);
    }

    public function test_mark_ready_returns_404_for_invalid_token(): void
    {
        $response = $this->json(
            'PATCH',
            $this->collabUrl('does-not-exist').'/ready',
            [],
            ['Accept' => 'application/json'],
        );
        $response->assertStatus(404);
    }

    public function test_public_update_returns_404_for_invalid_token(): void
    {
        $response = $this->json(
            'PUT',
            $this->collabUrl('does-not-exist'),
            ['metadata' => $this->metadata],
            ['Accept' => 'application/json'],
        );

        $response->assertStatus(404);
    }

    private function createNotification()
    {
        $response = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => null,
                'user_id' => 3,
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header,
        );

        return $response->decodeResponseJson()['data'];
    }

    private function createTeam(array $userIds, array $notificationIds)
    {
        $response = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Team Test '.fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => fake()->randomElement([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                    TeamMemberOf::NCS,
                ]),
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => $notificationIds,
                'users' => $userIds,
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $teamId = $response->decodeResponseJson()['data'];

        foreach ($userIds as $userId) {
            $this->json(
                'PUT',
                'api/v1/teams/'.$teamId.'/users/'.$userId,
                [
                    'roles' => [
                        'custodian.metadata.manager' => true,
                    ],
                ],
                $this->header,
            )->assertStatus(200);
        }

        return $teamId;
    }
}
