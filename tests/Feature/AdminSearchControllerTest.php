<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tool;
use App\Jobs\ReindexTypesenseEntity;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;

class AdminSearchControllerTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/admin/search';

    protected function setUp(): void
    {
        $this->commonSetUp();
    }

    public function test_status_returns_entity_and_feature_state(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/status', [], $this->header);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'entities' => [
                        '*' => ['entity', 'model', 'collection', 'collectionExists', 'documentCount', 'databaseCount', 'eligibleCount', 'facetFields'],
                    ],
                    'features',
                ],
            ]);
    }

    public function test_status_eligible_count_excludes_inactive_rows_that_database_count_still_includes(): void
    {
        $before = $this->toolsEntityStatus();

        Tool::factory()->create(['status' => Tool::STATUS_ACTIVE]);
        Tool::factory()->create(['status' => Tool::STATUS_ARCHIVED]);

        $after = $this->toolsEntityStatus();

        // Both rows are still in the table, so the raw count includes them...
        $this->assertEquals($before['databaseCount'] + 2, $after['databaseCount']);
        // ...but only the ACTIVE one is actually indexable, so eligibleCount
        // shouldn't be inflated by the archived row the way databaseCount is.
        $this->assertEquals($before['eligibleCount'] + 1, $after['eligibleCount']);
    }

    private function toolsEntityStatus(): array
    {
        $response = $this->json('GET', self::TEST_URL . '/status', [], $this->header);
        $entities = $response->decodeResponseJson()['data']['entities'];

        return collect($entities)->firstWhere('entity', 'tools');
    }

    public function test_reindex_queues_job_for_known_entity(): void
    {
        Queue::fake();

        $response = $this->json('POST', self::TEST_URL . '/reindex', ['entity' => 'datasets'], $this->header);

        $response->assertStatus(202)
            ->assertJson(['message' => 'queued', 'entity' => 'datasets']);

        Queue::assertPushed(ReindexTypesenseEntity::class);
    }

    public function test_reindex_rejects_unknown_entity(): void
    {
        Queue::fake();

        $response = $this->json('POST', self::TEST_URL . '/reindex', ['entity' => 'not_a_real_entity'], $this->header);

        $response->assertStatus(422);
        Queue::assertNotPushed(ReindexTypesenseEntity::class);
    }

    public function test_toggle_feature_activates_and_deactivates(): void
    {
        $response = $this->json('POST', self::TEST_URL . '/feature', ['feature' => 'TypesenseSearch', 'enabled' => false], $this->header);
        $response->assertStatus(200)->assertJson(['feature' => 'TypesenseSearch', 'enabled' => false]);
        $this->assertFalse(Feature::active('TypesenseSearch'));

        $response = $this->json('POST', self::TEST_URL . '/feature', ['feature' => 'TypesenseSearch', 'enabled' => true], $this->header);
        $response->assertStatus(200)->assertJson(['feature' => 'TypesenseSearch', 'enabled' => true]);
        $this->assertTrue(Feature::active('TypesenseSearch'));
    }

    public function test_toggle_feature_rejects_unknown_feature(): void
    {
        $response = $this->json('POST', self::TEST_URL . '/feature', ['feature' => 'SomeRandomFeature', 'enabled' => true], $this->header);
        $response->assertStatus(422);
    }

    public function test_endpoints_reject_unauthorised_and_non_admin_users(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/status');
        $response->assertStatus(401);

        $this->authorisationUser(false);
        $jwt = $this->getAuthorisationJwt(false);
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];

        $response = $this->json('GET', self::TEST_URL . '/status', [], $header);
        $response->assertStatus(401);
    }
}
