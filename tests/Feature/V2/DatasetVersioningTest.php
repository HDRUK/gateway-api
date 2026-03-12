<?php

namespace Tests\Feature\V2;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Enums\TeamMemberOf;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Illuminate\Support\Facades\Queue;

/**
 * Tests for delta-based metadata versioning (GAT-7181).
 *
 * Strategy:
 *  - v1 is a full base snapshot (patch = null).
 *  - v2–v9 are deltas (patch = RFC 6902 array, metadata = reduced envelope).
 *  - v10 is a materialised full snapshot again (patch = null).
 *
 * The tests exercise:
 *  1. Correct RFC 6902 patch content when a single field changes.
 *  2. Version list endpoint returns the right set of rows.
 *  3. Version reconstruction endpoint correctly rebuilds full GWDM metadata
 *     for any version (v1 snapshot, v2 delta, v3 delta after two edits).
 *  4. The 10th version materialises as a full snapshot (patch = null).
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

    // -------------------------------------------------------------------------
    // Test 1 — Patch content for a single-field change
    // -------------------------------------------------------------------------

    /**
     * When only `summary.title` is changed on the first update, the v2 delta
     * row's patch must contain exactly one RFC 6902 "replace" operation targeting
     * the title path.
     */
    public function test_delta_patch_contains_replace_operation_for_changed_field(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $originalTitle = $this->metadata['metadata']['summary']['title'];

        // Create dataset (v1 — base snapshot)
        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        // Update with only the title changed (v2 — first delta)
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

        // v1 is a full snapshot — patch must be null
        $this->assertNull($v1->patch, 'v1 base snapshot must have patch = null');

        // v2 must be a delta — patch must not be null
        $this->assertNotNull($v2->patch, 'v2 delta must have a patch');

        // The patch must be a non-empty array (RFC 6902 operations)
        $this->assertIsArray($v2->patch);
        $this->assertNotEmpty($v2->patch);

        // Find the operation that targets the title field.
        // The GWDM summary.title lives at the JSON path /summary/title inside the
        // GWDM metadata object (DatasetService diffs the inner GWDM object).
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

    // -------------------------------------------------------------------------
    // Test 2 — Version list endpoint
    // -------------------------------------------------------------------------

    /**
     * GET /api/v2/datasets/{id}/versions must return one entry per stored version
     * with at minimum: id, version, title, created_at fields.
     */
    public function test_list_versions_returns_all_stored_versions(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        // Apply two more updates so we have 3 versions
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

        // Unauthenticated GET (active dataset endpoint is public)
        $response = $this->json('GET', self::TEST_URL_DATASET_V3 . '/' . $datasetId . '/versions');
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $data = $response->decodeResponseJson()['data'] ?? [];
        $this->assertCount(3, $data, 'Three version entries expected (v1, v2, v3)');

        $versionNumbers = array_column($data, 'version');
        $this->assertContains(1, $versionNumbers);
        $this->assertContains(2, $versionNumbers);
        $this->assertContains(3, $versionNumbers);

        // Each entry must expose at least these fields
        foreach ($data as $entry) {
            $this->assertArrayHasKey('id', $entry);
            $this->assertArrayHasKey('version', $entry);
            $this->assertArrayHasKey('title', $entry);
            $this->assertArrayHasKey('created_at', $entry);
        }
    }

    // -------------------------------------------------------------------------
    // Test 3 — Version reconstruction (rollup)
    // -------------------------------------------------------------------------

    /**
     * GET /api/v2/datasets/{id}/version/{version} must reconstruct the correct
     * full GWDM metadata at every version, whether it is a base snapshot or a
     * delta that requires replaying patches.
     *
     * Scenario:
     *   v1 — created with original title
     *   v2 — title changed to "Title v2"
     *   v3 — title changed to "Title v3", abstract also changed
     *
     * We then assert:
     *   GET …/version/1 → title = original
     *   GET …/version/2 → title = "Title v2"
     *   GET …/version/3 → title = "Title v3", abstract = updated value
     */
    public function test_version_reconstruction_returns_correct_metadata_at_each_version(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $originalTitle    = $this->metadata['metadata']['summary']['title'];
        $originalAbstract = $this->metadata['metadata']['summary']['abstract'] ?? null;

        // v1 — base snapshot
        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        // v2 — title only changed
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

        // v3 — title and abstract changed
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

        // Response shape: {"message":"success","data":{"gwdmVersion":"2.0","metadata":{...}}}
        // The inner GWDM object is at data.metadata, not data.metadata.metadata.

        // --- Reconstruct v1 ---
        $respV1 = $this->json('GET', self::TEST_URL_DATASET_V3 . '/' . $datasetId . '/version/1');
        $respV1->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $gwdmV1 = $respV1->decodeResponseJson()['data']['metadata'] ?? null;
        $this->assertNotNull($gwdmV1, 'v1 response must contain a GWDM metadata object');
        $this->assertEquals($originalTitle, $gwdmV1['summary']['title'] ?? null, 'v1 title must match original');

        // --- Reconstruct v2 (one delta replayed) ---
        $respV2 = $this->json('GET', self::TEST_URL_DATASET_V3 . '/' . $datasetId . '/version/2');
        $respV2->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $gwdmV2 = $respV2->decodeResponseJson()['data']['metadata'] ?? null;
        $this->assertNotNull($gwdmV2, 'v2 response must contain a GWDM metadata object');
        $this->assertEquals('Title v2', $gwdmV2['summary']['title'] ?? null, 'v2 title must be "Title v2"');

        // --- Reconstruct v3 (two deltas replayed from v1 snapshot) ---
        $respV3 = $this->json('GET', self::TEST_URL_DATASET_V3 . '/' . $datasetId . '/version/3');
        $respV3->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $gwdmV3 = $respV3->decodeResponseJson()['data']['metadata'] ?? null;
        $this->assertNotNull($gwdmV3, 'v3 response must contain a GWDM metadata object');
        $this->assertEquals('Title v3', $gwdmV3['summary']['title'] ?? null, 'v3 title must be "Title v3"');
        $this->assertEquals('Updated abstract for v3', $gwdmV3['summary']['abstract'] ?? null, 'v3 abstract must be updated value');
    }

    /**
     * Requesting a version that does not exist must return 404.
     */
    public function test_show_version_returns_404_for_nonexistent_version(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        $response = $this->json('GET', self::TEST_URL_DATASET_V3 . '/' . $datasetId . '/version/99');
        $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    // -------------------------------------------------------------------------
    // Test 4 — 10th version materialises as a full snapshot
    // -------------------------------------------------------------------------

    /**
     * After 9 delta updates the 10th version row must have patch = null
     * (materialised full snapshot), capping reconstruction cost at ≤9 deltas.
     */
    public function test_tenth_version_is_materialised_snapshot(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        // Perform 9 further updates (total 10 versions: v1 base + v2–v10)
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

        // v10 is a materialised snapshot — patch must be null
        $this->assertNull(
            $v10->patch,
            'v10 (every 10th version) must be a materialised full snapshot with patch = null'
        );

        // Confirm intermediate versions (v2–v9) are all deltas
        for ($v = 2; $v <= 9; $v++) {
            $row = $versions->firstWhere('version', $v);
            $this->assertNotNull($row, "v{$v} row must exist");
            $this->assertNotNull($row->patch, "v{$v} must be a delta (patch not null)");
        }

        // Also verify v10 can be correctly reconstructed via the API
        $resp = $this->json('GET', self::TEST_URL_DATASET_V3 . '/' . $datasetId . '/version/10');
        $resp->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $gwdm = $resp->decodeResponseJson()['data']['metadata'] ?? null;
        $this->assertNotNull($gwdm);
        $this->assertEquals('Title iteration 10', $gwdm['summary']['title'] ?? null);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a notification, team, and user in one call.
     * Returns [$teamId, $userId].
     */
    private function createTeamAndUser(): array
    {
        $notificationId = $this->createNotification();
        $teamId = $this->createTeam([], [$notificationId]);
        $userId = $this->createUser();
        return [$teamId, $userId];
    }

    /**
     * POST /api/v2/datasets and return the new dataset ID.
     */
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
