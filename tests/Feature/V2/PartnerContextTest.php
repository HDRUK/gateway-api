<?php

namespace Tests\Feature\V2;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Enums\TeamMemberOf;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Illuminate\Support\Facades\Http;

/**
 * Tests for partner_context isolation (GAT-8924).
 *
 * Two creation paths are covered:
 *   - POST /api/v2/datasets           (DatasetController via DatasetService)
 *   - POST /api/v2/teams/{id}/datasets (TeamDatasetController via MetadataOnboard trait)
 *
 * Read isolation assertions:
 *   - HDRUK (default, no header) sees ALL datasets regardless of partner_context.
 *   - CRUK (x-partner-context: CRUK) only sees datasets where partner_context = 'CRUK'.
 *   - CRUK receives 404 when requesting a dataset owned by another partner.
 */
class PartnerContextTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const DATASETS_URL      = '/api/v2/datasets';
    public const SEARCH_DATASETS_URL = '/api/v1/search/datasets';
    public const TEAMS_URL         = '/api/v1/teams';
    public const NOTIFICATIONS_URL = '/api/v1/notifications';
    public const USERS_URL         = '/api/v1/users';

    protected array $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->metadata = $this->getMetadata();
    }

    // -------------------------------------------------------------------------
    // Write path — partner_context is stamped at creation
    // -------------------------------------------------------------------------

    public function test_v2_endpoint_stamps_cruk_partner_context_when_header_present(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $response = $this->json(
            'POST',
            self::DATASETS_URL,
            [
                'team_id'       => $teamId,
                'user_id'       => $userId,
                'metadata'      => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status'        => Dataset::STATUS_ACTIVE,
            ],
            array_merge($this->header, ['x-partner-context' => 'CRUK']),
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $datasetId = $response->json('data');

        $this->assertSame('CRUK', Dataset::find($datasetId)->partner_context);
    }

    public function test_v2_endpoint_defaults_to_hdruk_partner_context_when_no_header(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $response = $this->json(
            'POST',
            self::DATASETS_URL,
            [
                'team_id'       => $teamId,
                'user_id'       => $userId,
                'metadata'      => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status'        => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $datasetId = $response->json('data');

        $this->assertSame('HDRUK', Dataset::find($datasetId)->partner_context);
    }

    public function test_team_endpoint_stamps_cruk_partner_context_when_header_present(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $response = $this->json(
            'POST',
            $this->teamDatasetsUrl($teamId),
            [
                'user_id'       => $userId,
                'metadata'      => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status'        => Dataset::STATUS_ACTIVE,
            ],
            array_merge($this->header, ['x-partner-context' => 'CRUK']),
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $datasetId = $response->json('data');

        $this->assertSame('CRUK', Dataset::find($datasetId)->partner_context);
    }

    public function test_team_endpoint_defaults_to_hdruk_partner_context_when_no_header(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $response = $this->json(
            'POST',
            $this->teamDatasetsUrl($teamId),
            [
                'user_id'       => $userId,
                'metadata'      => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status'        => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $datasetId = $response->json('data');

        $this->assertSame('HDRUK', Dataset::find($datasetId)->partner_context);
    }

    // -------------------------------------------------------------------------
    // Read path — listing isolation
    // -------------------------------------------------------------------------

    public function test_hdruk_context_index_returns_all_datasets(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $initialCount = Dataset::count();

        $this->createDataset($teamId, $userId);
        $this->createDataset($teamId, $userId, 'CRUK');

        // No x-partner-context header → HDRUK context → sees both
        $response = $this->json('GET', self::DATASETS_URL, [], $this->header);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertCount($initialCount + 2, $response->json('data'));
    }

    public function test_cruk_context_index_returns_only_cruk_datasets(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $initialCrukCount = Dataset::where('partner_context', 'CRUK')->count();

        $crukHeaders = array_merge($this->header, ['x-partner-context' => 'CRUK']);

        // Create one HDRUK dataset (no partner context header)
        $this->json('POST', self::DATASETS_URL, [
            'team_id' => $teamId, 'user_id' => $userId,
            'metadata' => $this->metadata,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ], $this->header)->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        // Create two CRUK datasets using explicit inline headers
        $crukId1 = $this->json('POST', self::DATASETS_URL, [
            'team_id' => $teamId, 'user_id' => $userId,
            'metadata' => $this->metadata,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ], $crukHeaders)->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))->json('data');

        $crukId2 = $this->json('POST', self::DATASETS_URL, [
            'team_id' => $teamId, 'user_id' => $userId,
            'metadata' => $this->metadata,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ], $crukHeaders)->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))->json('data');

        // Verify DB state
        $this->assertSame('CRUK', Dataset::find($crukId1)->partner_context, 'crukId1');
        $this->assertSame('CRUK', Dataset::find($crukId2)->partner_context, 'crukId2');
        $this->assertSame(
            $initialCrukCount + 2,
            Dataset::where('partner_context', 'CRUK')->count()
        );

        $response = $this->json('GET', self::DATASETS_URL . '?with_metadata=0', [], $crukHeaders);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // CRUK context sees only its own 2 datasets
        $this->assertCount($initialCrukCount + 2, $response->json('data'));
    }

    public function test_cruk_context_search_returns_only_cruk_datasets(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $hdrukDatasetId = $this->createDataset($teamId, $userId, 'HDRUK');
        $crukDatasetId = $this->createDataset($teamId, $userId, 'CRUK');

        $this->mockSearchHitsForDatasetIds([$hdrukDatasetId, $crukDatasetId]);

        $response = $this->json(
            'POST',
            self::SEARCH_DATASETS_URL,
            ['query' => null],
            array_merge($this->header, ['x-partner-context' => 'CRUK']),
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $returnedIds = array_map('intval', array_column($response->json('data'), '_id'));
        $this->assertSame([$crukDatasetId], $returnedIds);
        $this->assertSame(1, $response->json('total'));
        $this->assertSame(1, $response->json('elastic_total'));
    }

    public function test_hdruk_context_search_returns_all_partner_datasets(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();

        $hdrukDatasetId = $this->createDataset($teamId, $userId, 'HDRUK');
        $crukDatasetId = $this->createDataset($teamId, $userId, 'CRUK');

        $this->mockSearchHitsForDatasetIds([$hdrukDatasetId, $crukDatasetId]);

        $response = $this->json(
            'POST',
            self::SEARCH_DATASETS_URL,
            ['query' => null],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $returnedIds = array_map('intval', array_column($response->json('data'), '_id'));
        $this->assertEqualsCanonicalizing([$hdrukDatasetId, $crukDatasetId], $returnedIds);
        $this->assertSame(2, $response->json('total'));
    }

    // -------------------------------------------------------------------------
    // Read path — single-dataset show isolation
    // -------------------------------------------------------------------------

    public function test_hdruk_context_can_show_cruk_dataset(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $crukDatasetId = $this->createDataset($teamId, $userId, 'CRUK');

        // HDRUK (no header) should see the CRUK dataset
        $response = $this->json(
            'GET',
            self::DATASETS_URL . '/' . $crukDatasetId,
            [],
            $this->header,
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }

    public function test_cruk_context_receives_404_for_hdruk_dataset(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $hdrukDatasetId = $this->createDataset($teamId, $userId, 'HDRUK');

        $response = $this->json(
            'GET',
            self::DATASETS_URL . '/' . $hdrukDatasetId,
            [],
            array_merge($this->header, ['x-partner-context' => 'CRUK']),
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        $response->assertJson(['message' => 'Dataset not found']);
    }

    public function test_cruk_context_can_show_its_own_dataset(): void
    {
        [$teamId, $userId] = $this->createTeamAndUser();
        $crukDatasetId = $this->createDataset($teamId, $userId, 'CRUK');

        $response = $this->json(
            'GET',
            self::DATASETS_URL . '/' . $crukDatasetId,
            [],
            array_merge($this->header, ['x-partner-context' => 'CRUK']),
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }

    // -------------------------------------------------------------------------
    // Feature flag — allow_cross_context_read
    // -------------------------------------------------------------------------

    public function test_hdruk_context_index_excludes_other_partner_datasets_when_cross_context_read_disabled(): void
    {
        config(['partners.allow_cross_context_read' => false]);

        [$teamId, $userId] = $this->createTeamAndUser();
        $initialHdrukCount = Dataset::where('partner_context', 'HDRUK')->count();

        $this->createDataset($teamId, $userId, 'HDRUK');
        $this->createDataset($teamId, $userId, 'CRUK');

        $response = $this->json('GET', self::DATASETS_URL, [], $this->header);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // Flag off → HDRUK sees only its own datasets
        $this->assertCount($initialHdrukCount + 1, $response->json('data'));

        config(['partners.allow_cross_context_read' => true]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a dataset via the v2 endpoint and return its ID.
     * Partner context is applied via the x-partner-context header.
     */
    private function createDataset(
        int $teamId,
        int $userId,
        string $partnerContext = 'HDRUK',
    ): int {
        $headers = $partnerContext !== 'HDRUK'
            ? array_merge($this->header, ['x-partner-context' => $partnerContext])
            : $this->header;

        $response = $this->json(
            'POST',
            self::DATASETS_URL,
            [
                'team_id'       => $teamId,
                'user_id'       => $userId,
                'metadata'      => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status'        => Dataset::STATUS_ACTIVE,
            ],
            $headers,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return $response->json('data');
    }

    /**
     * Stub the external search service to return hits for the given dataset IDs.
     *
     * @param  array<int>  $datasetIds
     */
    private function mockSearchHitsForDatasetIds(array $datasetIds): void
    {
        $hits = array_map(fn (int $id) => [
            '_id' => (string) $id,
            '_source' => [
                'abstract' => '',
                'description' => '',
                'keywords' => '',
                'named_entities' => [],
                'publisherName' => '',
                'shortTitle' => 'Dataset ' . $id,
                'title' => 'Dataset ' . $id,
                'dataUseTitles' => [],
                'populationSize' => 1000,
            ],
            'highlight' => [
                'abstract' => [],
                'description' => [],
            ],
        ], $datasetIds);

        $searchUrl = config('gateway.search_service_url') . '/search/datasets*';

        // Replace setUp search stubs so this test controls returned hit IDs.
        $factory = Http::getFacadeRoot();
        $reflection = new \ReflectionClass($factory);
        $property = $reflection->getProperty('stubCallbacks');
        $property->setAccessible(true);
        $property->setValue($factory, collect());

        Http::fake([
            $searchUrl => Http::response([
                'took' => 1,
                'timed_out' => false,
                '_shards' => [],
                'hits' => [
                    'total' => ['value' => count($hits)],
                    'hits' => $hits,
                ],
                'aggregations' => [],
            ], 200),
        ]);
    }

    /**
     * Create a team and user, returning [$teamId, $userId].
     */
    private function createTeamAndUser(): array
    {
        $notificationId = $this->json(
            'POST',
            self::NOTIFICATIONS_URL,
            [
                'notification_type' => 'applicationSubmitted',
                'message'           => 'Test',
                'email'             => null,
                'user_id'           => 3,
                'opt_in'            => 1,
                'enabled'           => 1,
            ],
            $this->header,
        )->json('data');

        $teamId = $this->json(
            'POST',
            self::TEAMS_URL,
            [
                'name'                          => 'Partner Context Test Team ' . uniqid(),
                'enabled'                       => 1,
                'allows_messaging'              => 1,
                'workflow_enabled'              => 1,
                'access_requests_management'    => 1,
                'uses_5_safes'                  => 1,
                'is_admin'                      => 1,
                'member_of'                     => TeamMemberOf::OTHER,
                'contact_point'                 => 'test@example.com',
                'application_form_updated_by'   => 'Test',
                'application_form_updated_on'   => '2023-04-06 15:44:41',
                'notifications'                 => [$notificationId],
                'users'                         => [],
            ],
            $this->header,
        )->json('data');

        $userId = $this->json(
            'POST',
            self::USERS_URL,
            [
                'firstname'        => 'Test',
                'lastname'         => 'User',
                'email'            => 'test.partner.context.' . uniqid() . '@example.com',
                'password'         => 'Passw@rd1!',
                'sector_id'        => 1,
                'organisation'     => 'Test Org',
                'bio'              => 'Bio',
                'domain'           => 'https://example.com',
                'link'             => 'https://example.com/link',
                'orcid'            => 'https://orcid.org/00000000',
                'contact_feedback' => 1,
                'contact_news'     => 1,
                'mongo_id'         => random_int(100000, 999999),
                'mongo_object_id'  => uniqid(),
            ],
            $this->header,
        )->json('data');

        return [$teamId, $userId];
    }

    private function teamDatasetsUrl(int $teamId): string
    {
        return 'api/v2/teams/' . $teamId . '/datasets';
    }
}
