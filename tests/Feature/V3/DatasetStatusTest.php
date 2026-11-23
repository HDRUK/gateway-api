<?php

namespace Tests\Feature\V3;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetShareToken;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\TeamHasUser;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Illuminate\Testing\TestResponse;

class DatasetStatusTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_TEAM = '/api/v1/teams';

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
    }

    public function test_publishing_a_draft_makes_its_working_copy_the_first_version(): void
    {
        $datasetId = $this->createDataset(Dataset::STATUS_DRAFT);

        $this->changeStatus($datasetId, Dataset::STATUS_ACTIVE)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSame(Dataset::STATUS_ACTIVE, Dataset::findOrFail($datasetId)->status);
        $this->assertSame([1], $this->listedVersionNumbers($datasetId));
    }

    public function test_a_save_after_publishing_by_status_adds_a_version(): void
    {
        $datasetId = $this->createDataset(Dataset::STATUS_DRAFT);
        $this->changeStatus($datasetId, Dataset::STATUS_ACTIVE)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->save($datasetId, Dataset::STATUS_DRAFT);

        $this->assertSame(2, DatasetVersion::where('dataset_id', $datasetId)->count());
        $this->assertSame([1], $this->listedVersionNumbers($datasetId));
    }

    public function test_publishing_the_working_copy_of_a_published_dataset_adds_it_to_history(): void
    {
        $datasetId = $this->createDataset(Dataset::STATUS_ACTIVE);
        $this->save($datasetId, Dataset::STATUS_DRAFT);

        $this->changeStatus($datasetId, Dataset::STATUS_ACTIVE)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSame([1, 2], $this->listedVersionNumbers($datasetId));
    }

    public function test_a_draft_with_invalid_metadata_cannot_be_published(): void
    {
        $datasetId = $this->createDataset(Dataset::STATUS_DRAFT);
        $this->traserValidationErrors = ['summary.title is required'];

        $this->changeStatus($datasetId, Dataset::STATUS_ACTIVE)
            ->assertStatus(422);

        $this->assertSame(Dataset::STATUS_DRAFT, Dataset::findOrFail($datasetId)->status);
        $this->assertSame([], $this->listedVersionNumbers($datasetId));
    }

    public function test_a_draft_invalid_in_the_schema_it_was_submitted_in_cannot_be_published(): void
    {
        $datasetId = $this->createDataset(Dataset::STATUS_DRAFT, '?input_schema=HDRUK&input_version=4.0.0');
        $this->traserInvalidSchemas = ['HDRUK'];

        $this->changeStatus($datasetId, Dataset::STATUS_ACTIVE)
            ->assertStatus(422);

        $this->assertSame(Dataset::STATUS_DRAFT, Dataset::findOrFail($datasetId)->status);
    }

    public function test_publishing_by_status_closes_the_datasets_open_share_links(): void
    {
        $datasetId = $this->createDataset(Dataset::STATUS_DRAFT);
        $shareToken = $this->openShareToken($datasetId);

        $this->changeStatus($datasetId, Dataset::STATUS_ACTIVE)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertNotNull($shareToken->fresh()->revoked_at);
    }

    public function test_a_refused_publish_leaves_share_links_open(): void
    {
        $datasetId = $this->createDataset(Dataset::STATUS_DRAFT);
        $shareToken = $this->openShareToken($datasetId);
        $this->traserValidationErrors = ['summary.title is required'];

        $this->changeStatus($datasetId, Dataset::STATUS_ACTIVE)
            ->assertStatus(422);

        $this->assertNull($shareToken->fresh()->revoked_at);
    }

    public function test_archiving_keeps_the_published_versions(): void
    {
        $datasetId = $this->createDataset(Dataset::STATUS_ACTIVE);

        $this->changeStatus($datasetId, Dataset::STATUS_ARCHIVED)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSame(Dataset::STATUS_ARCHIVED, Dataset::findOrFail($datasetId)->status);
        $this->assertSame(1, DatasetVersion::where('dataset_id', $datasetId)->count());
        $this->assertSame([1], $this->listedVersionNumbers($datasetId));
    }

    public function test_republishing_an_archived_dataset_does_not_revalidate_its_published_version(): void
    {
        $datasetId = $this->createDataset(Dataset::STATUS_ACTIVE);
        $this->changeStatus($datasetId, Dataset::STATUS_ARCHIVED)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->traserValidationErrors = ['summary.title is required'];

        $this->changeStatus($datasetId, Dataset::STATUS_ACTIVE)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSame(Dataset::STATUS_ACTIVE, Dataset::findOrFail($datasetId)->status);
        $this->assertSame(1, DatasetVersion::where('dataset_id', $datasetId)->count());
    }

    public function test_moving_a_published_dataset_to_draft_keeps_its_versions(): void
    {
        $datasetId = $this->createDataset(Dataset::STATUS_ACTIVE);

        $this->changeStatus($datasetId, Dataset::STATUS_DRAFT)
            ->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertSame(Dataset::STATUS_DRAFT, Dataset::findOrFail($datasetId)->status);
        $this->assertSame([1], $this->listedVersionNumbers($datasetId));
    }

    public function test_an_unknown_dataset_is_not_found(): void
    {
        $this->changeStatus((int) Dataset::max('id') + 1000, Dataset::STATUS_ACTIVE)
            ->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    public function test_a_member_of_another_team_cannot_change_the_status(): void
    {
        $datasetId = $this->createDataset(Dataset::STATUS_DRAFT);
        $otherTeam = Team::factory()->create();

        $this->changeStatus($datasetId, Dataset::STATUS_ACTIVE, $this->metadataManagerHeaderForTeam($otherTeam->id))
            ->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

        $this->assertSame(Dataset::STATUS_DRAFT, Dataset::findOrFail($datasetId)->status);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $datasetId = $this->createDataset(Dataset::STATUS_DRAFT);

        $this->changeStatus($datasetId, 'PUBLISHED')
            ->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));

        $this->assertSame(Dataset::STATUS_DRAFT, Dataset::findOrFail($datasetId)->status);
    }

    private function createDataset(string $status, string $query = ''): int
    {
        $team = Team::factory()->create();

        $response = $this->json(
            'POST',
            '/api/v3/teams/' . $team->id . '/datasets' . $query,
            [
                'metadata' => $this->getMetadata(),
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => $status,
            ],
            $this->header,
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return $response->json('data');
    }

    private function openShareToken(int $datasetId): DatasetShareToken
    {
        $dataset = Dataset::findOrFail($datasetId);

        return DatasetShareToken::create([
            'team_id' => $dataset->team_id,
            'dataset_id' => $dataset->id,
            'created_by' => (int) $this->getUserFromJwt($this->getAuthorisationJwt())['id'],
            'token' => bin2hex(random_bytes(16)),
            'expires_at' => now()->addDay(),
        ]);
    }

    private function save(int $datasetId, string $status): void
    {
        $this->json(
            'PUT',
            '/api/v3/datasets/' . $datasetId,
            [
                'user_id' => (int) $this->getUserFromJwt($this->getAuthorisationJwt())['id'],
                'metadata' => $this->getMetadata(),
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => $status,
            ],
            $this->header,
        )->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }

    private function changeStatus(int $datasetId, string $status, ?array $headers = null): TestResponse
    {
        return $this->json(
            'PATCH',
            '/api/v3/datasets/' . $datasetId,
            ['status' => $status],
            $headers ?? $this->header,
        );
    }

    /**
     * @return list<int>
     */
    private function listedVersionNumbers(int $datasetId): array
    {
        $response = $this->json('GET', '/api/v3/datasets/' . $datasetId . '/versions');
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        return array_column($response->json('data'), 'version');
    }

    private function metadataManagerHeaderForTeam(int $teamId): array
    {
        $this->authorisationUser(false);
        $jwt = $this->getAuthorisationJwt(false);
        $metadataManager = $this->getUserFromJwt($jwt);

        TeamHasUser::create([
            'team_id' => $teamId,
            'user_id' => (int) $metadataManager['id'],
        ]);

        $this->json(
            'POST',
            self::TEST_URL_TEAM . '/' . $teamId . '/users',
            [
                'userId' => $metadataManager['id'],
                'roles' => ['custodian.metadata.manager'],
            ],
            $this->header,
        )->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
    }
}
