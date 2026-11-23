<?php

namespace Tests\Feature\V3;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\TeamHasUser;
use App\Models\User;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Illuminate\Testing\TestResponse;

class DatasetCreateTest extends TestCase
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

    public function test_a_dataset_created_as_a_draft_belongs_to_the_team_and_has_no_published_version(): void
    {
        $team = Team::factory()->create();

        $response = $this->createViaV3($team->id, ['status' => Dataset::STATUS_DRAFT]);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $dataset = Dataset::findOrFail($response->json('data'));
        $this->assertSame($team->id, (int) $dataset->team_id);
        $this->assertSame(Dataset::STATUS_DRAFT, $dataset->status);
        $this->assertSame([], $this->listedVersionNumbers($dataset->id));
    }

    public function test_a_dataset_created_as_published_lists_its_first_version(): void
    {
        $team = Team::factory()->create();

        $response = $this->createViaV3($team->id, ['status' => Dataset::STATUS_ACTIVE]);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $this->assertSame([1], $this->listedVersionNumbers($response->json('data')));
    }

    public function test_the_team_in_the_body_is_ignored(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();

        $response = $this->createViaV3($team->id, ['team_id' => $otherTeam->id]);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $this->assertSame($team->id, (int) Dataset::findOrFail($response->json('data'))->team_id);
    }

    public function test_identifiers_in_the_body_are_ignored(): void
    {
        $team = Team::factory()->create();
        $existing = Dataset::findOrFail($this->createViaV3($team->id)->json('data'));

        $response = $this->createViaV3($team->id, ['pid' => $existing->pid, 'datasetid' => 'chosen-by-caller']);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $created = Dataset::findOrFail($response->json('data'));
        $this->assertNotSame($existing->pid, $created->pid);
        $this->assertSame(1, Dataset::where('pid', $existing->pid)->count());
        $this->assertNull($created->datasetid);
    }

    public function test_the_dataset_is_saved_by_the_signed_in_user_not_the_user_in_the_body(): void
    {
        $team = Team::factory()->create();
        $signedInUserId = (int) $this->getUserFromJwt($this->getAuthorisationJwt())['id'];
        $otherUser = User::where('id', '!=', $signedInUserId)->firstOrFail();

        $response = $this->createViaV3($team->id, ['user_id' => $otherUser->id]);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $this->assertSame($signedInUserId, (int) Dataset::findOrFail($response->json('data'))->user_id);
    }

    public function test_the_gwdm_version_header_sets_the_stored_version(): void
    {
        $team = Team::factory()->create();

        $response = $this->createViaV3(
            $team->id,
            ['metadata' => $this->getMetadataV2p1()],
            [...$this->header, 'x-gwdm-version' => '2.1'],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $this->assertSame(
            '2.1',
            DatasetVersion::where('dataset_id', $response->json('data'))->where('version', 1)->firstOrFail()->gwdm_version,
        );
    }

    public function test_an_unknown_team_is_not_found(): void
    {
        $unknownTeamId = (int) Team::max('id') + 1000;

        $this->createViaV3($unknownTeamId)
            ->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));

        $this->assertSame(0, Dataset::where('team_id', $unknownTeamId)->count());
    }

    public function test_a_member_of_another_team_cannot_create_a_dataset_for_this_team(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $datasetsBefore = Dataset::count();

        $this->createViaV3($team->id, [], $this->metadataManagerHeaderForTeam($otherTeam->id))
            ->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

        $this->assertSame($datasetsBefore, Dataset::count());
    }

    public function test_status_is_required(): void
    {
        $team = Team::factory()->create();

        $this->createViaV3($team->id, ['status' => null])
            ->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
    }

    public function test_a_dataset_without_a_title_is_rejected(): void
    {
        $team = Team::factory()->create();
        $metadata = $this->getMetadata();
        $metadata['metadata']['summary']['title'] = '';
        $datasetsBefore = Dataset::count();

        $this->createViaV3($team->id, ['metadata' => $metadata])
            ->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));

        $this->assertSame($datasetsBefore, Dataset::count());
    }

    public function test_a_team_member_cannot_put_a_dataset_into_cohort_discovery(): void
    {
        $team = Team::factory()->create();

        $response = $this->createViaV3(
            $team->id,
            ['is_cohort_discovery' => true],
            $this->metadataManagerHeaderForTeam($team->id),
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $this->assertFalse((bool) Dataset::findOrFail($response->json('data'))->is_cohort_discovery);
    }

    public function test_an_admin_can_put_a_dataset_into_cohort_discovery(): void
    {
        $team = Team::factory()->create();

        $response = $this->createViaV3($team->id, ['is_cohort_discovery' => true]);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $this->assertTrue((bool) Dataset::findOrFail($response->json('data'))->is_cohort_discovery);
    }

    private function createViaV3(int $teamId, array $overrides = [], ?array $headers = null): TestResponse
    {
        $body = array_filter(
            [
                'metadata' => $this->getMetadata(),
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
                ...$overrides,
            ],
            fn ($value) => $value !== null,
        );

        return $this->json('POST', '/api/v3/teams/' . $teamId . '/datasets', $body, $headers ?? $this->header);
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
