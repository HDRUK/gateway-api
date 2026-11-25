<?php

namespace Tests\Feature\V3;

use Config;
use Tests\TestCase;
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
