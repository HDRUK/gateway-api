<?php

namespace Tests\Feature\V2;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Enums\TeamMemberOf;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * Delta-based metadata versioning (GAT-7181): v1 snapshot, v2-v9 deltas, v10 re-snapshots.
 */
class DatasetVersioningTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_DATASET    = '/api/v2/datasets';
    public const TEST_URL_DATASET_V3 = '/api/v3/datasets';
    public const TEST_URL_TEAM = '/api/v1/teams';
    public const TEST_URL_NOTIFICATION = '/api/v1/notifications';
    public const TEST_URL_USER = '/api/v1/users';

    protected $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->metadata = $this->getMetadata();
    }

    public function test_delta_patch_contains_replace_operation_for_changed_field(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $originalTitle = $this->metadata['metadata']['summary']['title'];

        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        $updatedMetadata = $this->metadata;
        $updatedMetadata['metadata']['summary']['title'] = 'Patched Title v2';

        $response = $this->json(
            'PUT',
            self::TEST_URL_DATASET_V3 . '/' . $datasetId,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $updatedMetadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $dsv = DatasetVersion::where('dataset_id', $datasetId)->orderBy('version')->get();
        $this->assertCount(2, $dsv, 'Expected exactly two version rows (v1 snapshot + v2 delta)');

        $v1 = $dsv[0];
        $v2 = $dsv[1];

        $this->assertNull($v1->patch, 'v1 base snapshot must have patch = null');
        $this->assertNotNull($v2->patch, 'v2 delta must have a patch');
        $this->assertIsArray($v2->patch);
        $this->assertNotEmpty($v2->patch);

        // GWDM summary.title is diffed at /summary/title, not /metadata/summary/title
        $titleOp = null;
        foreach ($v2->patch as $op) {
            if (($op['path'] ?? '') === '/summary/title' && ($op['op'] ?? '') === 'replace') {
                $titleOp = $op;
                break;
            }
        }

        $this->assertNotNull(
            $titleOp,
            'v2 patch must contain a "replace" operation for /summary/title. Actual patch: ' .
            json_encode($v2->patch)
        );

        $this->assertEquals('Patched Title v2', $titleOp['value'] ?? null);
    }

    public function test_list_versions_returns_all_stored_versions(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        foreach (['Title v2', 'Title v3'] as $newTitle) {
            $updated = $this->metadata;
            $updated['metadata']['summary']['title'] = $newTitle;
            $this->json(
                'PUT',
                self::TEST_URL_DATASET_V3 . '/' . $datasetId,
                [
                    'team_id' => $teamId,
                    'user_id' => $userId,
                    'metadata' => $updated,
                    'create_origin' => Dataset::ORIGIN_MANUAL,
                    'status' => Dataset::STATUS_ACTIVE,
                ],
                $this->header,
            )->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        }

        // active dataset endpoint is public — no auth header needed
        $response = $this->json('GET', self::TEST_URL_DATASET_V3 . '/' . $datasetId . '/versions');
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $data = $response->decodeResponseJson()['data'] ?? [];
        $this->assertCount(3, $data, 'Three version entries expected (v1, v2, v3)');

        $versionNumbers = array_column($data, 'version');
        $this->assertContains(1, $versionNumbers);
        $this->assertContains(2, $versionNumbers);
        $this->assertContains(3, $versionNumbers);

        foreach ($data as $entry) {
            $this->assertArrayHasKey('id', $entry);
            $this->assertArrayHasKey('version', $entry);
            $this->assertArrayHasKey('title', $entry);
            $this->assertArrayHasKey('created_at', $entry);
        }
    }

    public function test_version_reconstruction_returns_correct_metadata_at_each_version(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $originalTitle    = $this->metadata['metadata']['summary']['title'];
        $originalAbstract = $this->metadata['metadata']['summary']['abstract'] ?? null;

        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        $v2Metadata = $this->metadata;
        $v2Metadata['metadata']['summary']['title'] = 'Title v2';
        $this->json(
            'PUT',
            self::TEST_URL_DATASET_V3 . '/' . $datasetId,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $v2Metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        )->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $v3Metadata = $this->metadata;
        $v3Metadata['metadata']['summary']['title']    = 'Title v3';
        $v3Metadata['metadata']['summary']['abstract'] = 'Updated abstract for v3';
        $this->json(
            'PUT',
            self::TEST_URL_DATASET_V3 . '/' . $datasetId,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $v3Metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        )->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertCount(
            3,
            DatasetVersion::where('dataset_id', $datasetId)->get(),
            'Three version rows must exist before reconstruction tests'
        );

        // gwdm object is at data.metadata, not data.metadata.metadata
        $respV1 = $this->json('GET', self::TEST_URL_DATASET_V3 . '/' . $datasetId . '/version/1');
        $respV1->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $gwdmV1 = $respV1->decodeResponseJson()['data']['metadata'] ?? null;
        $this->assertNotNull($gwdmV1, 'v1 response must contain a GWDM metadata object');
        $this->assertEquals($originalTitle, $gwdmV1['summary']['title'] ?? null, 'v1 title must match original');

        $respV2 = $this->json('GET', self::TEST_URL_DATASET_V3 . '/' . $datasetId . '/version/2');
        $respV2->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $gwdmV2 = $respV2->decodeResponseJson()['data']['metadata'] ?? null;
        $this->assertNotNull($gwdmV2, 'v2 response must contain a GWDM metadata object');
        $this->assertEquals('Title v2', $gwdmV2['summary']['title'] ?? null, 'v2 title must be "Title v2"');

        $respV3 = $this->json('GET', self::TEST_URL_DATASET_V3 . '/' . $datasetId . '/version/3');
        $respV3->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $gwdmV3 = $respV3->decodeResponseJson()['data']['metadata'] ?? null;
        $this->assertNotNull($gwdmV3, 'v3 response must contain a GWDM metadata object');
        $this->assertEquals('Title v3', $gwdmV3['summary']['title'] ?? null, 'v3 title must be "Title v3"');
        $this->assertEquals('Updated abstract for v3', $gwdmV3['summary']['abstract'] ?? null, 'v3 abstract must be updated value');
    }

    public function test_show_version_returns_404_for_nonexistent_version(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        $response = $this->json('GET', self::TEST_URL_DATASET_V3 . '/' . $datasetId . '/version/99');
        $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    public function test_tenth_version_is_materialised_snapshot(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        for ($i = 2; $i <= 10; $i++) {
            $updated = $this->metadata;
            $updated['metadata']['summary']['title'] = "Title iteration {$i}";
            $this->json(
                'PUT',
                self::TEST_URL_DATASET_V3 . '/' . $datasetId,
                [
                    'team_id' => $teamId,
                    'user_id' => $userId,
                    'metadata' => $updated,
                    'create_origin' => Dataset::ORIGIN_MANUAL,
                    'status' => Dataset::STATUS_ACTIVE,
                ],
                $this->header,
            )->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        }

        $versions = DatasetVersion::where('dataset_id', $datasetId)
            ->orderBy('version')
            ->get();

        $this->assertCount(10, $versions, 'Ten version rows must exist after 9 updates');

        $v10 = $versions->firstWhere('version', 10);
        $this->assertNotNull($v10, 'v10 row must exist');
        $this->assertNull(
            $v10->patch,
            'v10 (every 10th version) must be a materialised full snapshot with patch = null'
        );

        for ($v = 2; $v <= 9; $v++) {
            $row = $versions->firstWhere('version', $v);
            $this->assertNotNull($row, "v{$v} row must exist");
            $this->assertNotNull($row->patch, "v{$v} must be a delta (patch not null)");
        }

        $resp = $this->json('GET', self::TEST_URL_DATASET_V3 . '/' . $datasetId . '/version/10');
        $resp->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $gwdm = $resp->decodeResponseJson()['data']['metadata'] ?? null;
        $this->assertNotNull($gwdm);
        $this->assertEquals('Title iteration 10', $gwdm['summary']['title'] ?? null);
    }

    private function createTeamAndUser(): array
    {
        $notificationId = $this->createNotification();
        $teamId = $this->createTeam([], [$notificationId]);
        $userId = $this->createUser();
        return [$teamId, $userId];
    }

    private function createDataset(int $teamId, int $userId, array $metadata): int
    {
        $response = $this->json(
            'POST',
            self::TEST_URL_DATASET,
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
        return $response->decodeResponseJson()['data'];
    }

    private function createNotification(): int
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

    private function createTeam(array $userIds, array $notificationIds): int
    {
        $response = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Team Versioning ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
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
                'contact_point' => 'versioning-test@test.com',
                'application_form_updated_by' => 'Test User',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => $notificationIds,
                'users' => $userIds,
            ],
            $this->header,
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        return $response->decodeResponseJson()['data'];
    }

    private function createUser(): int
    {
        $response = $this->json(
            'POST',
            self::TEST_URL_USER,
            [
                'firstname' => 'Version',
                'lastname' => 'Tester',
                'email' => 'version.tester.' . fake()->numerify('######') . '@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => 'https://orcid.org/75697342',
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => fake()->randomNumber(7),
                'mongo_object_id' => fake()->regexify('[a-z0-9]{10}'),
            ],
            $this->header,
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        return $response->decodeResponseJson()['data'];
    }
}
