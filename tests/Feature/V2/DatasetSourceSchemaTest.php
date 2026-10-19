<?php

namespace Tests\Feature\V2;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Enums\TeamMemberOf;
use App\Services\GatewayMetadataIngestionService;
use App\Services\Gwdm\GwdmMetadataHandler;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * The schema a dataset was submitted in (its ingestion format, e.g. HDRUK
 * 4.1.0) is recorded on every dataset_versions row it writes, separately from
 * the GWDM version it is stored as.
 *
 * Drafts depend on it most: they are translated without validation and are
 * re-edited from original_metadata, so the editor must know what format that
 * original submission is in.
 */
class DatasetSourceSchemaTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private const DATASETS_URL = '/api/v2/datasets';
    private const DATASETS_V3_URL = '/api/v3/datasets';

    protected array $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->metadata = $this->getMetadata();
    }

    // -------------------------------------------------------------------------
    // Team endpoint - the onboarding form's create and update paths
    // -------------------------------------------------------------------------

    public function test_team_create_records_the_schema_declared_in_the_envelope(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $datasetId = $this->createViaTeam($teamId, $userId, $this->declared('HDRUK', '4.1.0'));

        $this->assertSourceSchema($datasetId, 'HDRUK', '4.1.0');
    }

    public function test_team_create_records_a_draft_s_declared_schema(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $datasetId = $this->createViaTeam(
            $teamId,
            $userId,
            $this->declared('HDRUK', '4.1.0'),
            Dataset::STATUS_DRAFT,
        );

        $this->assertSourceSchema($datasetId, 'HDRUK', '4.1.0');
    }

    public function test_team_create_prefers_the_input_schema_query_parameters(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        // The fixture metadata is genuinely GWDM, which the TRASER test double
        // passes through unchanged; a non-GWDM declaration here would make it
        // substitute a different fixture.
        $datasetId = $this->createViaTeam(
            $teamId,
            $userId,
            $this->declared('HDRUK', '4.1.0'),
            Dataset::STATUS_ACTIVE,
            '?input_schema=GWDM&input_version=2.0',
        );

        $this->assertSourceSchema($datasetId, 'GWDM', '2.0');
    }

    public function test_team_create_records_nothing_when_nothing_is_declared(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $datasetId = $this->createViaTeam($teamId, $userId, $this->metadata);

        $this->assertSourceSchema($datasetId, null, null);
    }

    public function test_team_update_records_the_newly_declared_schema(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam(
            $teamId,
            $userId,
            $this->declared('HDRUK', '4.0.0'),
            Dataset::STATUS_DRAFT,
        );

        $this->json(
            'PUT',
            'api/v2/teams/' . $teamId . '/datasets/' . $datasetId,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->declared('HDRUK', '4.1.0'),
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        )->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSourceSchema($datasetId, 'HDRUK', '4.1.0');
    }

    // -------------------------------------------------------------------------
    // Dataset endpoints - service create, legacy v2 update, v3 versioned update
    // -------------------------------------------------------------------------

    public function test_v2_create_records_the_declared_schema(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $datasetId = $this->createViaDatasets($teamId, $userId, $this->declared('HDRUK', '4.1.0'));

        $this->assertSourceSchema($datasetId, 'HDRUK', '4.1.0');
    }

    public function test_v2_update_records_the_declared_schema_on_the_overwritten_row(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaDatasets($teamId, $userId, $this->metadata);

        $this->update(self::DATASETS_URL, $datasetId, $teamId, $userId, $this->declared('CRUK', '1.0.0'));

        $this->assertSourceSchema($datasetId, 'CRUK', '1.0.0');
    }

    public function test_v3_update_records_the_declared_schema_on_the_new_delta_row(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaDatasets($teamId, $userId, $this->declared('HDRUK', '4.0.0'));

        $updated = $this->declared('HDRUK', '4.1.0');
        $updated['metadata']['summary']['title'] = 'Updated title';
        $this->update(self::DATASETS_V3_URL, $datasetId, $teamId, $userId, $updated);

        $rows = DatasetVersion::where('dataset_id', $datasetId)->orderBy('version')->get();
        $this->assertCount(2, $rows);
        $this->assertNotNull($rows[1]->patch, 'expected a delta row');
        $this->assertSame(['HDRUK', '4.0.0'], [$rows[0]->source_schema_model, $rows[0]->source_schema_version]);
        $this->assertSame(['HDRUK', '4.1.0'], [$rows[1]->source_schema_model, $rows[1]->source_schema_version]);
    }

    // -------------------------------------------------------------------------
    // GMI - pull ingestion, always HDRUK
    // -------------------------------------------------------------------------

    public function test_gmi_ingestion_records_hdruk_with_an_undetected_version(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $pid = 'gmi-' . uniqid();

        $gmi = app(GatewayMetadataIngestionService::class);
        $gmi->setTeam($teamId);
        $gmi->storeMetadata([
            'user_id' => $userId,
            'team_id' => $teamId,
            'pid' => $pid,
            'metadata' => $this->metadata,
            'create_origin' => Dataset::ORIGIN_GMI,
            'status' => Dataset::STATUS_ACTIVE,
        ], app(GwdmMetadataHandler::class));

        $this->assertSourceSchema(Dataset::where('pid', $pid)->value('id'), 'HDRUK', null);
    }

    // -------------------------------------------------------------------------
    // API responses
    // -------------------------------------------------------------------------

    public function test_show_reports_the_source_schema(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaDatasets($teamId, $userId, $this->declared('HDRUK', '4.1.0'));

        $this->json('GET', self::DATASETS_URL . '/' . $datasetId, [], $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonPath('data.source_schema', ['model' => 'HDRUK', 'version' => '4.1.0']);
    }

    public function test_show_reports_a_null_source_schema_when_unknown(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaDatasets($teamId, $userId, $this->metadata);

        $response = $this->json('GET', self::DATASETS_URL . '/' . $datasetId, [], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        // Present but null, so the payload shape never changes.
        $this->assertArrayHasKey('source_schema', $response->json('data'));
        $this->assertNull($response->json('data.source_schema'));
    }

    public function test_index_reports_the_source_schema(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaDatasets($teamId, $userId, $this->declared('CRUK', '1.0.0'));

        $response = $this->json('GET', self::DATASETS_URL . '?per_page=1000', [], $this->header);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $row = collect($response->json('data'))->firstWhere('id', $datasetId);
        $this->assertSame(['model' => 'CRUK', 'version' => '1.0.0'], $row['source_schema']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function declared(string $model, string $version): array
    {
        return array_merge($this->metadata, [
            'schemaModel' => $model,
            'schemaVersion' => $version,
        ]);
    }

    private function assertSourceSchema(int $datasetId, ?string $model, ?string $version): void
    {
        $row = DatasetVersion::where('dataset_id', $datasetId)->orderByDesc('version')->first();

        $this->assertSame(
            [$model, $version],
            [$row->source_schema_model, $row->source_schema_version],
        );
    }

    private function createViaTeam(
        int $teamId,
        int $userId,
        array $metadata,
        string $status = Dataset::STATUS_ACTIVE,
        string $query = '',
    ): int {
        $response = $this->json(
            'POST',
            'api/v2/teams/' . $teamId . '/datasets' . $query,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => $status,
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return $response->json('data');
    }

    private function createViaDatasets(int $teamId, int $userId, array $metadata): int
    {
        $response = $this->json(
            'POST',
            self::DATASETS_URL,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return $response->json('data');
    }

    private function update(string $url, int $datasetId, int $teamId, int $userId, array $metadata): void
    {
        $this->json(
            'PUT',
            $url . '/' . $datasetId,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        )->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }

    private function createTeamAndUser(): array
    {
        $notificationId = $this->json('POST', '/api/v1/notifications', [
            'notification_type' => 'applicationSubmitted',
            'message' => 'Test',
            'email' => null,
            'user_id' => 3,
            'opt_in' => 1,
            'enabled' => 1,
        ], $this->header)->json('data');

        $teamId = $this->json('POST', '/api/v1/teams', [
            'name' => 'Source Schema Test Team ' . uniqid(),
            'enabled' => 1,
            'allows_messaging' => 1,
            'workflow_enabled' => 1,
            'access_requests_management' => 1,
            'uses_5_safes' => 1,
            'is_admin' => 1,
            'member_of' => TeamMemberOf::OTHER,
            'contact_point' => 'test@example.com',
            'application_form_updated_by' => 'Test',
            'application_form_updated_on' => '2023-04-06 15:44:41',
            'notifications' => [$notificationId],
            'users' => [],
        ], $this->header)->json('data');

        $userId = $this->json('POST', '/api/v1/users', [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test.source.schema.' . uniqid() . '@example.com',
            'password' => 'Passw@rd1!',
            'sector_id' => 1,
            'organisation' => 'Test Org',
            'bio' => 'Bio',
            'domain' => 'https://example.com',
            'link' => 'https://example.com/link',
            'orcid' => 'https://orcid.org/00000000',
            'contact_feedback' => 1,
            'contact_news' => 1,
        ], $this->header)->json('data');

        return [$teamId, $userId];
    }
}
