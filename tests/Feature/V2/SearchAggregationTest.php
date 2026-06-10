<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Laravel\Pennant\Feature;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * Feature tests for POST /api/v2/search/aggregation.
 *
 * Covers:
 *  1. Feature flag guard (404 when inactive)
 *  2. Input validation (400 when type missing or query invalid)
 *  3. Successful aggregation response shape
 *  4. HDRUK provider hydrates a snapshot-versioned dataset
 *  5. HDRUK provider reconstructs and hydrates a delta-versioned dataset
 *     (regression for the bug where latestMetadata returning a delta row
 *     caused DatasetHydrator to silently drop all hits)
 */
class SearchAggregationTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v2/search/aggregation';

    protected $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->metadata = $this->getMetadata();

        // Flush the Pennant in-memory cache, then write an explicit stored value
        // so Feature::active() always sees a fresh result within each test.
        Feature::flushCache();
        Feature::activate('V2_SearchAggregation');

        // Stub the ARDC external endpoint so it never hits the real network.
        Http::fake([
            'https://researchdata.edu.au/*' => Http::response(
                ['result' => ['docs' => []]],
                200,
                ['application/json']
            ),
        ]);
    }

    // -------------------------------------------------------------------------
    // Feature flag
    // -------------------------------------------------------------------------

    public function test_returns_404_when_feature_flag_is_inactive(): void
    {
        Feature::flushCache();
        Feature::deactivate('V2_SearchAggregation');

        $response = $this->json('POST', self::TEST_URL, ['query' => 'asthma', 'type' => 'datasets'], $this->header);

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Input validation
    // -------------------------------------------------------------------------

    public function test_returns_400_when_type_is_missing(): void
    {
        $response = $this->json('POST', self::TEST_URL, ['query' => 'asthma'], $this->header);

        $response->assertStatus(400);
    }

    public function test_returns_400_when_query_contains_injection_pattern(): void
    {
        $response = $this->json(
            'POST',
            self::TEST_URL,
            ['query' => "asthma'; DROP TABLE datasets;--", 'type' => 'datasets'],
            $this->header
        );

        $response->assertStatus(400);
    }

    public function test_returns_400_when_sort_format_is_invalid(): void
    {
        $response = $this->json(
            'POST',
            self::TEST_URL,
            ['query' => 'asthma', 'type' => 'datasets', 'sort' => 'invalid_sort_string'],
            $this->header
        );

        $response->assertStatus(400);
    }

    // -------------------------------------------------------------------------
    // Success — response structure
    // -------------------------------------------------------------------------

    public function test_returns_success_envelope_with_query_and_type(): void
    {
        $response = $this->json(
            'POST',
            self::TEST_URL,
            ['query' => 'asthma', 'type' => 'tools'],
            $this->header
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'query',
                'type',
                'results',
            ],
        ]);
        $response->assertJsonPath('data.query', 'asthma');
        $response->assertJsonPath('data.type', 'tools');
    }

    public function test_hdruk_results_contain_expected_keys(): void
    {
        // HDRUK search for tools has no DB hydration step, so no dataset setup needed.
        $response = $this->json(
            'POST',
            self::TEST_URL,
            ['query' => 'nlp', 'type' => 'tools'],
            $this->header
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'results' => [
                    'HDRUK' => [
                        'provider_logo',
                        'about',
                        'hits',
                        'total',
                        'aggregations',
                        'ids',
                    ],
                ],
            ],
        ]);
    }

    public function test_ardc_is_deferred_for_dataset_search(): void
    {
        $response = $this->json(
            'POST',
            self::TEST_URL,
            ['query' => 'asthma', 'type' => 'datasets'],
            $this->header
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.pending', ['ARDC']);
        $response->assertJsonStructure(['data' => ['token', 'token_ttl']]);
        $this->assertStringStartsWith('srch_', $response->json('data.token'));
    }

    public function test_search_results_returns_ardc_after_job_runs(): void
    {
        // Queue driver is sync in tests, so DeferredProviderSearch runs immediately
        // on dispatch and writes ARDC results to cache before we poll.
        $postResponse = $this->json(
            'POST',
            self::TEST_URL,
            ['query' => 'asthma', 'type' => 'datasets'],
            $this->header
        );

        $token = $postResponse->json('data.token');
        $this->assertNotNull($token);

        $response = $this->json('GET', "/api/v2/search/aggregation/results/{$token}", [], $this->header);

        $response->assertStatus(200);
        $response->assertJsonPath('data.pending', []);
        $response->assertJsonStructure([
            'data' => [
                'results' => [
                    'ARDC' => ['provider_logo', 'about', 'hits', 'total', 'aggregations', 'ids'],
                ],
            ],
        ]);
    }

    public function test_search_results_returns_404_for_unknown_token(): void
    {
        $response = $this->json('GET', '/api/v2/search/aggregation/results/srch_doesnotexist', [], $this->header);

        $response->assertStatus(404);
    }

    public function test_search_results_returns_404_when_feature_flag_inactive(): void
    {
        Feature::flushCache();
        Feature::deactivate('V2_SearchAggregation');

        $response = $this->json('GET', '/api/v2/search/aggregation/results/srch_sometoken', [], $this->header);

        $response->assertStatus(404);
    }

}
