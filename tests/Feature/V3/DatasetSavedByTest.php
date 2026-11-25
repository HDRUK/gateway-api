<?php

namespace Tests\Feature\V3;

use Config;
use Tests\TestCase;
use App\Enums\DatasetOrigin;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\User;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Illuminate\Testing\TestResponse;

class DatasetSavedByTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
    }

    public function test_a_created_dataset_is_saved_by_the_signed_in_user_not_the_user_in_the_body(): void
    {
        $otherUser = User::where('id', '!=', $this->signedInUserId())->firstOrFail();

        $response = $this->createViaV3(Team::factory()->create()->id, ['user_id' => $otherUser->id]);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $this->assertSame($this->signedInUserId(), (int) Dataset::findOrFail($response->json('data'))->user_id);
    }

    public function test_an_update_without_a_user_in_the_body_is_saved_by_the_signed_in_user(): void
    {
        $dataset = $this->createdDataset(Team::factory()->create());

        $this->updateViaV3($dataset->id, [])
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSame($this->signedInUserId(), (int) $dataset->fresh()->user_id);
    }

    public function test_an_update_is_saved_by_the_signed_in_user_not_the_user_in_the_body(): void
    {
        $dataset = $this->createdDataset(Team::factory()->create());
        $otherUser = User::where('id', '!=', $this->signedInUserId())->firstOrFail();

        $this->updateViaV3($dataset->id, ['user_id' => $otherUser->id])
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSame($this->signedInUserId(), (int) $dataset->fresh()->user_id);
    }

    public function test_a_signed_in_user_cannot_label_a_created_dataset_as_federated(): void
    {
        $response = $this->createViaV3(Team::factory()->create()->id, ['create_origin' => Dataset::ORIGIN_GMI]);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $this->assertSame(Dataset::ORIGIN_MANUAL, Dataset::findOrFail($response->json('data'))->create_origin);
    }

    public function test_a_signed_in_user_cannot_label_an_update_as_federated(): void
    {
        $dataset = $this->createdDataset(Team::factory()->create());

        $this->updateViaV3($dataset->id, ['create_origin' => Dataset::ORIGIN_GMI])
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSame(Dataset::ORIGIN_MANUAL, $dataset->fresh()->create_origin);
    }

    public function test_a_dataset_created_with_integration_credentials_is_saved_by_the_integration_owner_via_the_api(): void
    {
        $team = Team::factory()->create();
        $integrationOwner = User::where('id', '!=', $this->signedInUserId())->firstOrFail();
        $integrationHeader = $this->createApp($team->id, $integrationOwner->id);

        $response = $this->createViaV3($team->id, ['create_origin' => Dataset::ORIGIN_GMI], $integrationHeader);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $dataset = Dataset::findOrFail($response->json('data'));
        $this->assertSame($integrationOwner->id, (int) $dataset->user_id);
        $this->assertSame(Dataset::ORIGIN_API, $dataset->create_origin);
    }

    public function test_an_update_with_integration_credentials_is_saved_by_the_integration_owner_via_the_api(): void
    {
        $team = Team::factory()->create();
        $dataset = $this->createdDataset($team);
        $integrationOwner = User::where('id', '!=', $this->signedInUserId())->firstOrFail();
        $integrationHeader = $this->createApp($team->id, $integrationOwner->id);

        $this->updateViaV3($dataset->id, ['user_id' => $this->signedInUserId()], $integrationHeader)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $dataset->refresh();
        $this->assertSame($integrationOwner->id, (int) $dataset->user_id);
        $this->assertSame(Dataset::ORIGIN_API, $dataset->create_origin);
    }

    public function test_the_first_version_records_the_signed_in_user_as_a_manual_save(): void
    {
        $dataset = $this->createdDataset(Team::factory()->create());

        $this->assertVersionSavedBy($dataset->id, 1, $this->signedInUserId(), DatasetOrigin::MANUAL);
    }

    public function test_the_first_version_records_the_integration_owner_as_an_api_save(): void
    {
        $team = Team::factory()->create();
        $integrationOwner = User::where('id', '!=', $this->signedInUserId())->firstOrFail();
        $integrationHeader = $this->createApp($team->id, $integrationOwner->id);

        $response = $this->createViaV3($team->id, [], $integrationHeader);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $this->assertVersionSavedBy($response->json('data'), 1, $integrationOwner->id, DatasetOrigin::API);
    }

    public function test_each_version_keeps_who_saved_it(): void
    {
        $team = Team::factory()->create();
        $dataset = $this->createdDataset($team);
        $integrationOwner = User::where('id', '!=', $this->signedInUserId())->firstOrFail();
        $integrationHeader = $this->createApp($team->id, $integrationOwner->id);

        $this->updateViaV3($dataset->id, [], $integrationHeader)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertVersionSavedBy($dataset->id, 1, $this->signedInUserId(), DatasetOrigin::MANUAL);
        $this->assertVersionSavedBy($dataset->id, 2, $integrationOwner->id, DatasetOrigin::API);
    }

    public function test_a_replaced_working_copy_records_who_saved_it_last(): void
    {
        $team = Team::factory()->create();
        $draft = Dataset::findOrFail($this->createViaV3($team->id, ['status' => Dataset::STATUS_DRAFT])->json('data'));
        $integrationOwner = User::where('id', '!=', $this->signedInUserId())->firstOrFail();
        $integrationHeader = $this->createApp($team->id, $integrationOwner->id);

        $this->updateViaV3($draft->id, ['status' => Dataset::STATUS_DRAFT], $integrationHeader)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertVersionSavedBy($draft->id, 1, $integrationOwner->id, DatasetOrigin::API);
    }

    public function test_a_version_overwritten_by_a_v2_dataset_update_no_longer_names_its_v3_saver(): void
    {
        $team = Team::factory()->create();
        $dataset = $this->createdDataset($team);

        $this->json('PUT', '/api/v2/datasets/' . $dataset->id, $this->v2UpdateBody($team->id), $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertVersionSaverUnknown($dataset->id, 1);
    }

    public function test_a_version_overwritten_by_a_v2_team_update_no_longer_names_its_v3_saver(): void
    {
        $team = Team::factory()->create();
        $dataset = $this->createdDataset($team);

        $this->json('PUT', '/api/v2/teams/' . $team->id . '/datasets/' . $dataset->id, $this->v2UpdateBody($team->id), $this->header)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertVersionSaverUnknown($dataset->id, 1);
    }

    public function test_the_v1_and_v2_dataset_responses_do_not_show_who_saved_a_version(): void
    {
        $dataset = $this->createdDataset(Team::factory()->create());

        foreach (['/api/v1/datasets/', '/api/v2/datasets/'] as $url) {
            $this->json('GET', $url . $dataset->id, [], $this->header)
                ->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
                ->assertJsonPath('data.versions.0.version', 1)
                ->assertJsonMissingPath('data.versions.0.user_id')
                ->assertJsonMissingPath('data.versions.0.origin');
        }
    }

    private function assertVersionSavedBy(int $datasetId, int $version, int $userId, DatasetOrigin $origin): void
    {
        $row = DatasetVersion::where('dataset_id', $datasetId)->where('version', $version)->firstOrFail();

        $this->assertSame($userId, $row->user_id);
        $this->assertSame($origin, $row->origin);
    }

    private function assertVersionSaverUnknown(int $datasetId, int $version): void
    {
        $row = DatasetVersion::where('dataset_id', $datasetId)->where('version', $version)->firstOrFail();

        $this->assertNull($row->user_id);
        $this->assertNull($row->origin);
    }

    private function v2UpdateBody(int $teamId): array
    {
        return [
            'team_id' => $teamId,
            'user_id' => $this->signedInUserId(),
            'metadata' => $this->getMetadata(),
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ];
    }

    private function signedInUserId(): int
    {
        return (int) $this->getUserFromJwt($this->getAuthorisationJwt())['id'];
    }

    private function createdDataset(Team $team): Dataset
    {
        $response = $this->createViaV3($team->id);
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return Dataset::findOrFail($response->json('data'));
    }

    private function createViaV3(int $teamId, array $overrides = [], ?array $headers = null): TestResponse
    {
        return $this->json(
            'POST',
            '/api/v3/teams/' . $teamId . '/datasets',
            [
                'metadata' => $this->getMetadata(),
                'status' => Dataset::STATUS_ACTIVE,
                ...$overrides,
            ],
            $headers ?? $this->header,
        );
    }

    private function updateViaV3(int $datasetId, array $overrides, ?array $headers = null): TestResponse
    {
        return $this->json(
            'PUT',
            '/api/v3/datasets/' . $datasetId,
            [
                'metadata' => $this->getMetadata(),
                'status' => Dataset::STATUS_ACTIVE,
                ...$overrides,
            ],
            $headers ?? $this->header,
        );
    }
}
