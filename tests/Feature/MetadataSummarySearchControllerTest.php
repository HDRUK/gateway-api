<?php

namespace Tests\Feature;

use App\Services\Search\MetadataSummarySearch;
use Mockery;
use Tests\TestCase;

/**
 * POST /api/v2/search/metadata-summary: the HTTP layer only.
 */
class MetadataSummarySearchControllerTest extends TestCase
{
    private const URL = '/api/v2/search/metadata-summary';

    private function enable(bool $on = true): void
    {
        config(['scout.driver' => 'typesense', 'typesense.summary_embedding_config.enabled' => $on]);
    }

    private function fakeService(): \Mockery\MockInterface
    {
        $service = Mockery::mock(MetadataSummarySearch::class);
        $this->app->instance(MetadataSummarySearch::class, $service);

        return $service;
    }

    public function test_returns_404_while_the_index_is_disabled(): void
    {
        $this->enable(false);
        $this->fakeService()->shouldNotReceive('search');

        $this->postJson(self::URL, ['terms' => ['asthma']])
            ->assertStatus(404)
            ->assertJson(['message' => 'Resource not found']);
    }

    public function test_passes_trimmed_terms_and_defaults_to_match_all(): void
    {
        $this->enable();
        $this->fakeService()->shouldReceive('search')
            ->once()
            ->with(['childhood asthma', 'copd'], MetadataSummarySearch::MATCH_ALL, null)
            ->andReturn(['terms' => ['childhood asthma', 'copd'], 'match' => 'all', 'datasets' => []]);

        $this->postJson(self::URL, ['terms' => ['  childhood asthma ', 'copd']])
            ->assertOk()
            ->assertJson(['message' => 'success', 'data' => ['match' => 'all', 'datasets' => []]]);
    }

    public function test_passes_match_and_dataset_ids_through(): void
    {
        $this->enable();
        $this->fakeService()->shouldReceive('search')
            ->once()
            ->with(['asthma'], MetadataSummarySearch::MATCH_ANY, [9, 5])
            ->andReturn(['terms' => ['asthma'], 'match' => 'any', 'datasets' => []]);

        $this->postJson(self::URL, ['terms' => ['asthma'], 'match' => 'any', 'dataset_ids' => [9, 5]])
            ->assertOk();
    }

    public function test_invalid_input_never_reaches_the_service(): void
    {
        $this->enable();
        $this->fakeService()->shouldNotReceive('search');

        // BaseFormRequest answers 400 INVALID_ARGUMENT, not Laravel's usual 422.
        $this->postJson(self::URL, ['terms' => ['a']])
            ->assertStatus(400)
            ->assertJsonPath('errors.0.field', 'terms.0');
    }
}
