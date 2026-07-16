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
 * GET /api/v2/datasets/{id}/metadata — dedicated metadata-only read endpoint.
 *
 * Replaces the old ?view=metadataOnly query param on GET /api/v2/datasets/{id}
 * (which varied the response shape of a shared route). This is its own route
 * with a single, consistent response shape:
 *   {"message":"success","data":{"gwdmVersion":"2.0","metadata":{...}}}
 */
class DatasetMetadataOnlyTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_DATASET = '/api/v2/datasets';
    public const TEST_URL_TEAM = '/api/v1/teams';
    public const TEST_URL_NOTIFICATION = '/api/v1/notifications';
    public const TEST_URL_USER = '/api/v1/users';

    protected $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->metadata = $this->getMetadataV2p0();
    }

    public function test_metadata_only_returns_gwdm_envelope(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        $response = $this->json('GET', self::TEST_URL_DATASET . '/' . $datasetId . '/metadata');

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $body = $response->decodeResponseJson();
        $this->assertSame('success', $body['message'] ?? null);
        $this->assertSame('2.0', $body['data']['gwdmVersion'] ?? null);
        $this->assertEquals(
            $this->metadata['metadata']['summary']['title'],
            $body['data']['metadata']['summary']['title'] ?? null,
        );

        // Only the envelope keys — no dataset-resource fields (id, pid, team, etc.)
        $this->assertArrayNotHasKey('pid', $body['data']);
        $this->assertArrayNotHasKey('team', $body['data']);
    }

    /**
     * A DRAFT dataset exists but is not publicly visible — mirrors the existing
     * "Dataset not found" convention asserted for showActive() in DatasetTest.php.
     */
    public function test_metadata_only_returns_404_for_non_active_dataset(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $response = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $draftDatasetId = $response->decodeResponseJson()['data'];

        $response = $this->json('GET', self::TEST_URL_DATASET . '/' . $draftDatasetId . '/metadata');

        $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        $response->assertJson(['message' => 'Dataset not found']);
    }

    /**
     * Exercises DatasetService::getMetadataOnly() directly rather than through
     * HTTP: the default HDRUK partner config (config/partners.php) always fills
     * in both schema_model and schema_version defaults, so the "one without the
     * other" mismatch can't be triggered by query params alone against the
     * default partner — it's a service-level guard, so test it at that level.
     */
    public function test_get_metadata_only_throws_when_schema_model_missing_version(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        $this->expectException(\InvalidArgumentException::class);

        app(\App\Services\DatasetService::class)->getMetadataOnly(
            Dataset::find($datasetId),
            'someModel',
            null,
        );
    }

    public function test_metadata_only_returns_404_when_requested_gwdm_version_has_no_rows(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $datasetId = $this->createDataset($teamId, $userId, $this->metadata);

        // Dataset was created at GWDM 2.0 — requesting 2.1 has no matching row.
        $response = $this->json(
            'GET',
            self::TEST_URL_DATASET . '/' . $datasetId . '/metadata',
            [],
            array_merge($this->header, ['x-gwdm-version' => '2.1']),
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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
                'name' => 'Team MetadataOnly ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
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
                'contact_point' => 'metadata-only-test@test.com',
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
                'firstname' => 'MetadataOnly',
                'lastname' => 'Tester',
                'email' => 'metadata.only.tester.' . fake()->numerify('######') . '@test.com',
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
