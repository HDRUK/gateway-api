<?php

namespace Tests\Feature\V2;

use App\Http\Enums\TeamMemberOf;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use Config;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * COLLAB-origin datasets (created via a share-token link, see
 * DatasetShareTokenTest/DatasetCollabTest) must be rejected by every normal,
 * authenticated update/edit path - they can only be edited through the
 * token URL. Also covers the JSON export endpoint used for the manual
 * "download and re-upload" step once a collaborative draft is finished.
 */
class DatasetCollabLockAndExportTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_TEAM = '/api/v1/teams';

    public const TEST_URL_NOTIFICATION = '/api/v1/notifications';

    protected $metadata;

    protected function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->metadata = $this->getMetadata();
    }

    private function team_datasets_url(int $teamId): string
    {
        return 'api/v2/teams/'.$teamId.'/datasets';
    }

    /**
     * Creates a normal MANUAL/ACTIVE dataset via the real create flow, then
     * flips its create_origin to COLLAB directly - simulating a dataset that
     * was created via a share token (bypassing the guarded write paths, the
     * same way DatasetShareTokenController::store does).
     */
    private function createCollabDataset(int $teamId): int
    {
        $create = $this->json(
            'POST',
            $this->team_datasets_url($teamId),
            [
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );
        $create->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $datasetId = $create->decodeResponseJson()['data'];

        Dataset::where('id', $datasetId)->update(['create_origin' => Dataset::ORIGIN_COLLAB]);

        return $datasetId;
    }

    public function test_v2_team_update_rejects_collab_origin_dataset(): void
    {
        $teamId = $this->createTeam();
        $datasetId = $this->createCollabDataset($teamId);

        $response = $this->json(
            'PUT',
            $this->team_datasets_url($teamId).'/'.$datasetId,
            [
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $response->assertStatus(403);
    }

    public function test_v2_team_edit_rejects_collab_origin_dataset(): void
    {
        $teamId = $this->createTeam();
        $datasetId = $this->createCollabDataset($teamId);

        $response = $this->json(
            'PATCH',
            $this->team_datasets_url($teamId).'/'.$datasetId,
            ['status' => Dataset::STATUS_ACTIVE],
            $this->header,
        );

        $response->assertStatus(403);
    }

    public function test_v2_dataset_update_rejects_collab_origin_dataset(): void
    {
        $teamId = $this->createTeam();
        $datasetId = $this->createCollabDataset($teamId);

        $response = $this->json(
            'PUT',
            '/api/v2/datasets/'.$datasetId,
            [
                'team_id' => $teamId,
                'user_id' => $this->currentUser['id'],
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $response->assertStatus(400);
    }

    public function test_v2_dataset_edit_rejects_collab_origin_dataset(): void
    {
        $teamId = $this->createTeam();
        $datasetId = $this->createCollabDataset($teamId);

        $response = $this->json(
            'PATCH',
            '/api/v2/datasets/'.$datasetId,
            ['status' => Dataset::STATUS_ACTIVE],
            $this->header,
        );

        $response->assertStatus(400);
    }

    public function test_v3_team_update_rejects_collab_origin_dataset(): void
    {
        $teamId = $this->createTeam();
        $datasetId = $this->createCollabDataset($teamId);

        $response = $this->json(
            'PUT',
            '/api/v3/teams/'.$teamId.'/datasets/'.$datasetId,
            [
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $response->assertStatus(400);
    }

    public function test_export_json_downloads_latest_metadata(): void
    {
        $teamId = $this->createTeam();
        $team = \App\Models\Team::where('id', $teamId)->first();

        // A real submission always has a data custodian picked via the UI's
        // Autocomplete - simulate that rather than relying on the bare test
        // fixture, which doesn't include one.
        $metadataWithCustodian = $this->metadata;
        $metadataWithCustodian['metadata']['summary']['dataCustodian'] = [
            'identifier' => $team->pid,
            'name' => $team->name,
        ];

        $create = $this->json(
            'POST',
            $this->team_datasets_url($teamId),
            [
                'metadata' => $metadataWithCustodian,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $datasetId = $create->decodeResponseJson()['data'];

        $response = $this->json(
            'GET',
            $this->team_datasets_url($teamId).'/'.$datasetId.'/export/json',
            [],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $response->assertHeader('Content-Disposition', 'attachment; filename="dataset-'.$datasetId.'.json"');

        $body = $response->decodeResponseJson();
        $this->assertEquals(
            $this->metadata['metadata']['summary']['title'],
            $body['summary']['title'],
        );
        $this->assertEquals($team->pid, $body['summary']['dataCustodian']['identifier']);
    }

    public function test_export_json_returns_422_when_data_custodian_identifier_is_missing(): void
    {
        $teamId = $this->createTeam();
        $team = \App\Models\Team::where('id', $teamId)->first();

        // Deliberately omit dataCustodian to prove the export endpoint
        // refuses to hand back metadata that would fail the upload flow's
        // own PID check, rather than silently exporting broken JSON.
        $create = $this->json(
            'POST',
            $this->team_datasets_url($teamId),
            [
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $datasetId = $create->decodeResponseJson()['data'];

        $response = $this->json(
            'GET',
            $this->team_datasets_url($teamId).'/'.$datasetId.'/export/json',
            [],
            $this->header,
        );

        $response->assertStatus(422);
    }

    /**
     * Regression test for the exact bug reported: exporting a COLLAB draft
     * (which, since the CollabMetadataSeed fix, always has its data
     * custodian identifier seeded from the team's pid) must return the bare
     * document with a real identifier at the top level - not the
     * {metadata, original_metadata, gwdmVersion} wrapper, which the upload
     * flow's checkIfPidMatches/createDatasetFromFile can't parse.
     */
    public function test_export_json_for_collab_draft_returns_unwrapped_document_with_team_identifier(): void
    {
        $teamId = $this->createTeam();
        $team = \App\Models\Team::where('id', $teamId)->first();

        $tokenResponse = $this->json(
            'POST',
            'api/v2/teams/'.$teamId.'/datasets/share-tokens',
            [],
            $this->header,
        );
        $datasetId = $tokenResponse->decodeResponseJson()['data']['dataset_id'];

        $response = $this->json(
            'GET',
            $this->team_datasets_url($teamId).'/'.$datasetId.'/export/json',
            [],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $body = $response->decodeResponseJson();

        // Unwrapped: summary is at the top level, not nested under "metadata".
        $this->assertArrayHasKey('summary', $body);
        $this->assertEquals($team->pid, $body['summary']['dataCustodian']['identifier'] ?? null);
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

    private function createTeam(): int
    {
        $notificationId = $this->createNotification();

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
                'notifications' => [$notificationId],
                'users' => [],
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return $response->decodeResponseJson()['data'];
    }
}
