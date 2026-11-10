<?php

namespace Tests\Feature\V2;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Enums\TeamMemberOf;
use App\Services\GatewayMetadataIngestionService;
use App\Services\Gwdm\GwdmMetadataHandler;
use Laravel\Pennant\Feature;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class FormHydrationTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected array $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->metadata = $this->getMetadata();

        Feature::flushCache();
        Feature::activate('SchemaOnboardingForm');
    }

    public function test_form_hydration_is_not_found_when_the_flag_is_off(): void
    {
        Feature::deactivate('SchemaOnboardingForm');
        [$teamId] = $this->createTeamAndUser();

        $this->json('GET', $this->url($teamId), [], $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    public function test_form_hydration_for_a_new_dataset(): void
    {
        [$teamId] = $this->createTeamAndUser();

        $response = $this->json('GET', $this->url($teamId), [], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data' => [
                    'schemas',
                    'model',
                    'version',
                    'schema',
                    'defaultValues',
                ],
            ])
            ->assertJsonPath('data.schemas', [
                ['model' => 'HDRUK', 'version' => '4.1.0'],
                ['model' => 'PRUK', 'version' => '1.0.0'],
                ['model' => 'CRUK', 'version' => '1.0.0'],
            ])
            ->assertJsonPath('data.model', 'HDRUK')
            ->assertJsonPath('data.version', '4.1.0')
            ->assertJsonPath('data.schema.title', 'HDRUK 4.1.0');

        $this->assertArrayNotHasKey('metadata', $response->json('data'));
    }

    public function test_form_hydration_for_a_requested_schema(): void
    {
        [$teamId] = $this->createTeamAndUser();

        $this->json('GET', $this->url($teamId) . '?model=PRUK&version=1.0.0', [], $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonPath('data.model', 'PRUK')
            ->assertJsonPath('data.version', '1.0.0')
            ->assertJsonPath('data.schema.title', 'PRUK 1.0.0');
    }

    public function test_form_hydration_rejects_a_schema_that_is_not_offered(): void
    {
        [$teamId] = $this->createTeamAndUser();

        $this->json('GET', $this->url($teamId) . '?model=UNKNOWN&version=1.0.0', [], $this->header)
            ->assertStatus(422);
        $this->json('GET', $this->url($teamId) . '?model=SchemaOrg&version=default', [], $this->header)
            ->assertStatus(422);
    }

    public function test_form_hydration_loads_the_original_metadata_when_the_source_schema_matches(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($teamId, $userId, $this->declared('HDRUK', '4.1.0'));

        $this->json('GET', $this->url($teamId) . '?dataset_id=' . $datasetId, [], $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonPath('data.model', 'HDRUK')
            ->assertJsonPath('data.version', '4.1.0')
            ->assertJsonPath('data.metadata', $this->latestVersion($datasetId)->metadata['original_metadata']);
    }

    public function test_form_hydration_keeps_only_the_summary_when_switching_to_another_schema(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($teamId, $userId, $this->declared('HDRUK', '4.1.0'));
        $summary = $this->latestVersion($datasetId)->metadata['original_metadata']['summary'];

        $response = $this->json('GET', $this->url($teamId) . '?dataset_id=' . $datasetId . '&model=PRUK&version=1.0.0', [], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonPath('data.model', 'PRUK');
        $this->assertEquals([
            'summary' => [
                'title' => $summary['title'],
                'abstract' => $summary['abstract'],
                'populationSize' => $summary['populationSize'],
            ],
        ], $response->json('data.metadata'));
    }

    public function test_form_hydration_opens_an_empty_form_when_translation_fails(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($teamId, $userId, $this->metadata);
        $this->traserUntranslatable = ['HDRUK'];

        $this->json('GET', $this->url($teamId) . '?dataset_id=' . $datasetId, [], $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonPath('data.metadata', null)
            ->assertJsonPath('data.defaultValues', []);
    }

    public function test_form_hydration_does_not_load_another_team_s_dataset(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        [$otherTeamId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($otherTeamId, $userId, $this->declared('HDRUK', '4.1.0'));

        $this->json('GET', $this->url($teamId) . '?dataset_id=' . $datasetId, [], $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    public function test_form_hydration_unwraps_original_metadata_stored_with_its_envelope(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($teamId, $userId, $this->declared('HDRUK', '4.1.0'));

        $row = $this->latestVersion($datasetId);
        $envelope = $row->metadata;
        $original = $envelope['original_metadata'];
        $envelope['original_metadata'] = $this->declared('HDRUK', '4.1.0');
        $envelope['original_metadata']['metadata'] = $original;
        $row->metadata = $envelope;
        $row->save();

        $this->json('GET', $this->url($teamId) . '?dataset_id=' . $datasetId, [], $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonPath('data.metadata', $original);
    }

    public function test_form_hydration_opens_an_older_source_version_in_the_offered_version(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($teamId, $userId, $this->declared('HDRUK', '4.0.0'));

        $this->json('GET', $this->url($teamId) . '?dataset_id=' . $datasetId, [], $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonPath('data.model', 'HDRUK')
            ->assertJsonPath('data.version', '4.1.0')
            ->assertJsonPath('data.metadata', $this->latestVersion($datasetId)->metadata['metadata']);
    }

    public function test_form_hydration_reads_the_envelope_when_the_columns_are_unset(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($teamId, $userId, $this->declared('CRUK', '1.0.0'));
        DatasetVersion::where('dataset_id', $datasetId)->update([
            'source_schema_model' => null,
            'source_schema_version' => null,
        ]);

        $this->json('GET', $this->url($teamId) . '?dataset_id=' . $datasetId, [], $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonPath('data.model', 'CRUK')
            ->assertJsonPath('data.version', '1.0.0')
            ->assertJsonPath('data.metadata', $this->latestVersion($datasetId)->metadata['original_metadata']);
    }

    public function test_form_hydration_opens_a_gmi_dataset_by_translation(): void
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
        $datasetId = Dataset::where('pid', $pid)->value('id');

        $this->json('GET', $this->url($teamId) . '?dataset_id=' . $datasetId, [], $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonPath('data.model', 'HDRUK')
            ->assertJsonPath('data.version', '4.1.0')
            ->assertJsonPath('data.metadata', $this->latestVersion($datasetId)->metadata['metadata']);
    }

    public function test_form_hydration_opens_an_unknown_source_in_the_partner_default(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createViaTeam($teamId, $userId, $this->metadata);

        $this->json('GET', $this->url($teamId) . '?dataset_id=' . $datasetId, [], $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonPath('data.model', 'HDRUK')
            ->assertJsonPath('data.version', '4.1.0')
            ->assertJsonPath('data.metadata', $this->latestVersion($datasetId)->metadata['metadata']);
    }

    private function url(int $teamId): string
    {
        return 'api/v2/teams/' . $teamId . '/form_hydration';
    }

    private function declared(string $model, string $version): array
    {
        return array_merge($this->metadata, [
            'schemaModel' => $model,
            'schemaVersion' => $version,
        ]);
    }

    private function latestVersion(int $datasetId): DatasetVersion
    {
        return DatasetVersion::where('dataset_id', $datasetId)->orderByDesc('version')->first();
    }

    private function createViaTeam(int $teamId, int $userId, array $metadata): int
    {
        $response = $this->json(
            'POST',
            'api/v2/teams/' . $teamId . '/datasets',
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return $response->json('data');
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
            'name' => 'Form Hydration Test Team ' . uniqid(),
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
            'email' => 'test.form.hydration.' . uniqid() . '@example.com',
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
