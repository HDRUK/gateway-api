<?php

namespace Tests\Feature;

use App\Jobs\LogSearchAnalytics;
use App\Jobs\SearchAnalyticsData;
use App\Services\BigQueryService;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use Tests\TestCase;

class LogSearchAnalyticsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_build_filter_used_includes_filters_and_data_source(): void
    {
        $job = new LogSearchAnalytics(new SearchAnalyticsData(
            uuid: 'abc-123',
            entityType: 'dataset',
            searchTerm: 'asthma',
            filters: ['dataset' => ['publisherName' => 'HDRUK']],
            dataSource: 'ARDC',
            entityIds: [],
            entitiesReturned: 0,
        ));

        $result = json_decode($job->buildFilterUsed(), true);

        $this->assertSame('ARDC', $result['dataSource']);
        $this->assertSame(['dataset' => ['publisherName' => 'HDRUK']], $result['filters']);
    }

    public function test_build_filter_used_carries_the_given_data_source_verbatim(): void
    {
        $job = new LogSearchAnalytics(new SearchAnalyticsData(
            uuid: 'abc-123',
            entityType: 'dataset',
            searchTerm: null,
            filters: [],
            dataSource: 'HDRUK',
            entityIds: [],
            entitiesReturned: 0,
        ));

        $result = json_decode($job->buildFilterUsed(), true);

        $this->assertSame('HDRUK', $result['dataSource']);
    }

    public function test_handle_logs_and_does_not_propagate_when_bigquery_insert_fails(): void
    {
        $handler = new TestHandler;
        $this->app->instance('log', new Logger(new MonologLogger('testing', [$handler])));
        Log::clearResolvedInstance('log');

        $bigQuery = Mockery::mock(BigQueryService::class);
        $bigQuery->shouldReceive('insertRow')->andThrow(new \RuntimeException('boom'));

        $job = new LogSearchAnalytics(new SearchAnalyticsData(
            uuid: 'abc-123',
            entityType: 'dataset',
            searchTerm: 'asthma',
            filters: [],
            dataSource: 'HDRUK',
            entityIds: [],
            entitiesReturned: 0,
        ));

        $job->handle($bigQuery);

        $this->assertTrue($handler->hasErrorRecords());
    }
}
