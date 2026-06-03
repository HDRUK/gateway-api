<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SearchAggregator;
use App\Contracts\SearchProvider;
use Mockery;

class SearchAggregatorTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeProvider(
        string $shortName,
        array $supportedTypes,
        array $result = [],
        bool $shouldReceiveSearch = true
    ): SearchProvider {
        $mock = Mockery::mock(SearchProvider::class);
        $mock->shouldReceive('getShortName')->andReturn($shortName)->byDefault();
        $mock->shouldReceive('getFullName')->andReturn($shortName . ' Full Name')->byDefault();
        $mock->shouldReceive('getProviderLogo')->andReturn(null)->byDefault();
        $mock->shouldReceive('getProviderBlurb')->andReturn(null)->byDefault();
        $mock->shouldReceive('getSupportedTypes')->andReturn($supportedTypes)->byDefault();

        if ($shouldReceiveSearch) {
            $mock->shouldReceive('search')
                ->andReturn(array_merge(
                    ['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []],
                    $result
                ))
                ->byDefault();
        }

        return $mock;
    }

    // -------------------------------------------------------------------------
    // Type routing
    // -------------------------------------------------------------------------

    public function test_only_calls_providers_that_support_the_requested_type(): void
    {
        $matching = $this->makeProvider('MATCH', ['datasets']);
        $matching->shouldReceive('search')->once()
            ->with('asthma', 'datasets', [])
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []]);

        $skipped = Mockery::mock(SearchProvider::class);
        $skipped->shouldReceive('getSupportedTypes')->andReturn(['tools']);
        $skipped->shouldNotReceive('search');

        $aggregator = new SearchAggregator([$matching, $skipped]);
        $result = $aggregator->search('asthma', 'datasets');

        $this->assertEquals('success', $result['message']);
        $this->assertArrayHasKey('MATCH', $result['data']['results']);
    }

    public function test_returns_failed_when_no_providers_support_the_type(): void
    {
        $provider = Mockery::mock(SearchProvider::class);
        $provider->shouldReceive('getSupportedTypes')->andReturn(['tools']);
        $provider->shouldNotReceive('search');

        $aggregator = new SearchAggregator([$provider]);
        $result = $aggregator->search('asthma', 'datasets');

        $this->assertEquals('failed', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_returns_failed_when_provider_list_is_empty(): void
    {
        $aggregator = new SearchAggregator([]);
        $result = $aggregator->search('asthma', 'datasets');

        $this->assertEquals('failed', $result['message']);
        $this->assertNull($result['data']);
    }

    // -------------------------------------------------------------------------
    // Resilience
    // -------------------------------------------------------------------------

    public function test_continues_and_returns_partial_results_when_one_provider_throws(): void
    {
        $failing = Mockery::mock(SearchProvider::class);
        $failing->shouldReceive('getShortName')->andReturn('FAIL');
        $failing->shouldReceive('getSupportedTypes')->andReturn(['datasets']);
        $failing->shouldReceive('search')->andThrow(new \RuntimeException('Search service unreachable'));

        $working = $this->makeProvider('WORK', ['datasets'], [
            'hits'  => [['_id' => '1', '_source' => ['title' => 'A dataset']]],
            'total' => 1,
        ]);

        $aggregator = new SearchAggregator([$failing, $working]);
        $result = $aggregator->search('asthma', 'datasets');

        $this->assertEquals('success', $result['message']);
        $this->assertArrayNotHasKey('FAIL', $result['data']['results']);
        $this->assertArrayHasKey('WORK', $result['data']['results']);
        $this->assertEquals(1, $result['data']['results']['WORK']['total']);
    }

    public function test_returns_failed_when_all_providers_throw(): void
    {
        $provider = Mockery::mock(SearchProvider::class);
        $provider->shouldReceive('getShortName')->andReturn('FAIL');
        $provider->shouldReceive('getSupportedTypes')->andReturn(['datasets']);
        $provider->shouldReceive('search')->andThrow(new \RuntimeException('down'));

        $aggregator = new SearchAggregator([$provider]);
        $result = $aggregator->search('asthma', 'datasets');

        $this->assertEquals('failed', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Response envelope
    // -------------------------------------------------------------------------

    public function test_response_envelope_contains_query_and_type(): void
    {
        $provider = $this->makeProvider('TEST', ['datasets'], [
            'hits'         => [],
            'total'        => 0,
            'aggregations' => [],
            'ids'          => [],
        ]);

        $aggregator = new SearchAggregator([$provider]);
        $result = $aggregator->search('cancer research', 'datasets', ['per_page' => 10]);

        $this->assertEquals('success', $result['message']);
        $this->assertEquals('cancer research', $result['data']['query']);
        $this->assertEquals('datasets', $result['data']['type']);
    }

    public function test_response_contains_provider_metadata(): void
    {
        $provider = Mockery::mock(SearchProvider::class);
        $provider->shouldReceive('getShortName')->andReturn('HDR');
        $provider->shouldReceive('getProviderLogo')->andReturn('https://example.com/logo.svg');
        $provider->shouldReceive('getProviderBlurb')->andReturn('<p>About HDR</p>');
        $provider->shouldReceive('getSupportedTypes')->andReturn(['datasets']);
        $provider->shouldReceive('search')->andReturn([
            'hits'         => [['_id' => '1']],
            'total'        => 1,
            'aggregations' => ['publisherName' => ['buckets' => []]],
            'ids'          => ['1'],
        ]);

        $aggregator = new SearchAggregator([$provider]);
        $result = $aggregator->search('asthma', 'datasets');

        $entry = $result['data']['results']['HDR'];

        $this->assertEquals('https://example.com/logo.svg', $entry['provider_logo']);
        $this->assertEquals('<p>About HDR</p>', $entry['about']);
        $this->assertCount(1, $entry['hits']);
        $this->assertEquals(1, $entry['total']);
        $this->assertIsArray($entry['aggregations']);
        $this->assertIsArray($entry['ids']);
        $this->assertContains('1', $entry['ids']);
    }

    public function test_params_are_forwarded_to_provider(): void
    {
        $expectedParams = ['sort' => 'score:desc', 'per_page' => 25, 'view_type' => 'mini'];

        $provider = Mockery::mock(SearchProvider::class);
        $provider->shouldReceive('getShortName')->andReturn('TEST');
        $provider->shouldReceive('getProviderLogo')->andReturn(null);
        $provider->shouldReceive('getProviderBlurb')->andReturn(null);
        $provider->shouldReceive('getSupportedTypes')->andReturn(['datasets']);
        $provider->shouldReceive('search')
            ->once()
            ->with('asthma', 'datasets', $expectedParams)
            ->andReturn(['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []]);

        $aggregator = new SearchAggregator([$provider]);
        $aggregator->search('asthma', 'datasets', $expectedParams);
    }

    // -------------------------------------------------------------------------
    // Multi-provider
    // -------------------------------------------------------------------------

    public function test_results_from_multiple_providers_are_keyed_by_short_name(): void
    {
        $hdruk = $this->makeProvider('HDRUK', ['datasets'], ['total' => 104]);
        $ardc  = $this->makeProvider('ARDC', ['datasets'], ['total' => 3]);

        $aggregator = new SearchAggregator([$hdruk, $ardc]);
        $result = $aggregator->search('asthma', 'datasets');

        $this->assertArrayHasKey('HDRUK', $result['data']['results']);
        $this->assertArrayHasKey('ARDC', $result['data']['results']);
        $this->assertEquals(104, $result['data']['results']['HDRUK']['total']);
        $this->assertEquals(3, $result['data']['results']['ARDC']['total']);
    }
}
