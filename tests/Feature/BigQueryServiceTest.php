<?php

namespace Tests\Unit\Services;

use App\Services\BigQueryService;
use Google\Cloud\BigQuery\BigNumeric;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\Date;
use Google\Cloud\BigQuery\Numeric;
use Google\Cloud\BigQuery\QueryJobConfiguration;
use Google\Cloud\BigQuery\Time;
use Google\Cloud\BigQuery\Timestamp;
use Mockery;
use ReflectionClass;
use ReflectionProperty;
use Tests\TestCase;

class BigQueryServiceTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_empty_array_when_no_rows(): void
    {
        $result = $this->makeService([])->query('SELECT 1');

        $this->assertSame([], $result);
    }

    public function test_passes_parameters_to_query_config(): void
    {
        $this->expectNotToPerformAssertions();

        $queryConfig = Mockery::mock(QueryJobConfiguration::class);
        $queryConfig->shouldReceive('parameters')
            ->once()
            ->with(['id' => 42])
            ->andReturnSelf();

        $client = Mockery::mock(BigQueryClient::class);
        $client->shouldReceive('query')->andReturn($queryConfig);
        $client->shouldReceive('runQuery')->andReturn(new \ArrayObject([]));

        $this->makeServiceWithClient($client)
            ->query('SELECT * FROM t WHERE id = @id', ['id' => 42]);
    }

    public function test_does_not_call_parameters_when_params_empty(): void
    {
        $this->expectNotToPerformAssertions();

        $queryConfig = Mockery::mock(QueryJobConfiguration::class);
        $queryConfig->shouldReceive('parameters')->never();

        $client = Mockery::mock(BigQueryClient::class);
        $client->shouldReceive('query')->andReturn($queryConfig);
        $client->shouldReceive('runQuery')->andReturn(new \ArrayObject([]));

        $this->makeServiceWithClient($client)->query('SELECT 1');
    }

    public function test_converts_date_to_string(): void
    {
        $date = Mockery::mock(Date::class);
        $date->shouldReceive('formatAsString')->andReturn('2024-01-15');

        $result = $this->makeService([['col' => $date]])->query('SELECT col FROM t');

        $this->assertSame('2024-01-15', $result[0]['col']);
    }

    public function test_converts_time_to_string(): void
    {
        $time = Mockery::mock(Time::class);
        $time->shouldReceive('formatAsString')->andReturn('13:45:00');

        $result = $this->makeService([['col' => $time]])->query('SELECT col FROM t');

        $this->assertSame('13:45:00', $result[0]['col']);
    }

    public function test_converts_timestamp_to_formatted_string(): void
    {
        $timestamp = Mockery::mock(Timestamp::class);
        $timestamp->shouldReceive('get')->andReturn(new \DateTime('2024-06-01 12:00:00'));

        $result = $this->makeService([['col' => $timestamp]])->query('SELECT col FROM t');

        $this->assertSame('2024-06-01 12:00:00', $result[0]['col']);
    }

    public function test_converts_numeric_to_float(): void
    {
        $numeric = Mockery::mock(Numeric::class);
        $numeric->shouldReceive('get')->andReturn('3.14');

        $result = $this->makeService([['col' => $numeric]])->query('SELECT col FROM t');

        $this->assertSame(3.14, $result[0]['col']);
    }

    public function test_converts_bignumeric_to_float(): void
    {
        $bigNumeric = Mockery::mock(BigNumeric::class);
        $bigNumeric->shouldReceive('get')->andReturn('9999999999.99');

        $result = $this->makeService([['col' => $bigNumeric]])->query('SELECT col FROM t');

        $this->assertSame(9999999999.99, $result[0]['col']);
    }

    public function test_passes_through_native_int(): void
    {
        $result = $this->makeService([['col' => 42]])->query('SELECT col FROM t');

        $this->assertSame(42, $result[0]['col']);
        $this->assertIsInt($result[0]['col']);
    }

    public function test_passes_through_native_float(): void
    {
        $result = $this->makeService([['col' => 1.5]])->query('SELECT col FROM t');

        $this->assertSame(1.5, $result[0]['col']);
        $this->assertIsFloat($result[0]['col']);
    }

    public function test_casts_numeric_string_with_decimal_to_float(): void
    {
        $result = $this->makeService([['col' => '2.71']])->query('SELECT col FROM t');

        $this->assertSame(2.71, $result[0]['col']);
        $this->assertIsFloat($result[0]['col']);
    }

    public function test_casts_numeric_string_without_decimal_to_int(): void
    {
        $result = $this->makeService([['col' => '100']])->query('SELECT col FROM t');

        $this->assertSame(100, $result[0]['col']);
        $this->assertIsInt($result[0]['col']);
    }

    public function test_passes_through_non_numeric_string(): void
    {
        $result = $this->makeService([['col' => 'hello']])->query('SELECT col FROM t');

        $this->assertSame('hello', $result[0]['col']);
    }

    public function test_passes_through_null(): void
    {
        $result = $this->makeService([['col' => null]])->query('SELECT col FROM t');

        $this->assertNull($result[0]['col']);
    }

    public function test_returns_multiple_rows(): void
    {
        $result = $this->makeService([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ])->query('SELECT id, name FROM t');

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertSame('Bob', $result[1]['name']);
    }

    private function makeService(array $rows): BigQueryService
    {
        $queryConfig = Mockery::mock(QueryJobConfiguration::class);
        $queryConfig->shouldReceive('parameters')->andReturnSelf();

        $client = Mockery::mock(BigQueryClient::class);
        $client->shouldReceive('query')->andReturn($queryConfig);
        $client->shouldReceive('runQuery')->andReturn(new \ArrayObject($rows));

        $service = (new ReflectionClass(BigQueryService::class))->newInstanceWithoutConstructor();

        (new ReflectionProperty(BigQueryService::class, 'client'))->setValue($service, $client);

        return $service;
    }

    private function makeServiceWithClient(object $client): BigQueryService
    {
        $service = (new ReflectionClass(BigQueryService::class))->newInstanceWithoutConstructor();

        (new ReflectionProperty(BigQueryService::class, 'client'))->setValue($service, $client);

        return $service;
    }
}
