<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\SearchProviders\HDRUK;
use ReflectionMethod;

/**
 * Unit tests for HDRUK::sort().
 *
 * Verifies that asc and desc produce symmetric, type-aware ordering:
 *  - Strings  → case-insensitive
 *  - Dates    → timestamp comparison (handles non-ISO formats)
 *  - Numerics → natural numeric order
 *  - Score    → passthrough (desc) or reverse (asc)
 */
class HdrukSortTest extends TestCase
{
    private function sort(array $hits, string $type, string $sortParam): array
    {
        $method = new ReflectionMethod(HDRUK::class, 'sort');
        $method->setAccessible(true);
        return $method->invoke(new HDRUK(), $hits, $type, $sortParam);
    }

    private function hits(array $sourceValues, string $field): array
    {
        return array_map(fn ($v) => ['_id' => '1', '_score' => 1.0, '_source' => [$field => $v]], $sourceValues);
    }

    private function sourceValues(array $hits, string $field): array
    {
        return array_map(fn ($h) => $h['_source'][$field], $hits);
    }

    // -------------------------------------------------------------------------
    // String sorting
    // -------------------------------------------------------------------------

    public function test_string_asc_is_case_insensitive(): void
    {
        $hits   = $this->hits(['Zebra', 'apple', 'Mango'], 'name');
        $result = $this->sort($hits, 'tools', 'name:asc');

        $this->assertSame(['apple', 'Mango', 'Zebra'], $this->sourceValues($result, 'name'));
    }

    public function test_string_desc_is_case_insensitive(): void
    {
        $hits   = $this->hits(['apple', 'Zebra', 'Mango'], 'name');
        $result = $this->sort($hits, 'tools', 'name:desc');

        $this->assertSame(['Zebra', 'Mango', 'apple'], $this->sourceValues($result, 'name'));
    }

    public function test_asc_and_desc_produce_reverse_of_each_other_for_strings(): void
    {
        $hits = $this->hits(['Charlie', 'alpha', 'Bravo'], 'name');

        $asc  = $this->sourceValues($this->sort($hits, 'tools', 'name:asc'), 'name');
        $desc = $this->sourceValues($this->sort($hits, 'tools', 'name:desc'), 'name');

        $this->assertSame(array_reverse($asc), $desc);
    }

    // -------------------------------------------------------------------------
    // Date sorting
    // -------------------------------------------------------------------------

    public function test_date_asc_orders_oldest_first(): void
    {
        $hits   = $this->hits(['2024-03-01', '2022-01-15', '2025-11-30'], 'created_at');
        $result = $this->sort($hits, 'datasets', 'created_at:asc');

        $this->assertSame(
            ['2022-01-15', '2024-03-01', '2025-11-30'],
            $this->sourceValues($result, 'created_at')
        );
    }

    public function test_date_desc_orders_newest_first(): void
    {
        $hits   = $this->hits(['2024-03-01', '2022-01-15', '2025-11-30'], 'created_at');
        $result = $this->sort($hits, 'datasets', 'created_at:desc');

        $this->assertSame(
            ['2025-11-30', '2024-03-01', '2022-01-15'],
            $this->sourceValues($result, 'created_at')
        );
    }

    public function test_asc_and_desc_produce_reverse_of_each_other_for_dates(): void
    {
        $hits = $this->hits(['2023-06-01', '2021-12-25', '2025-01-01'], 'updated_at');

        $asc  = $this->sourceValues($this->sort($hits, 'datasets', 'updated_at:asc'), 'updated_at');
        $desc = $this->sourceValues($this->sort($hits, 'datasets', 'updated_at:desc'), 'updated_at');

        $this->assertSame(array_reverse($asc), $desc);
    }

    // -------------------------------------------------------------------------
    // Numeric sorting
    // -------------------------------------------------------------------------

    public function test_numeric_asc_orders_smallest_first(): void
    {
        $hits   = $this->hits([30, 10, 20], 'count');
        $result = $this->sort($hits, 'tools', 'count:asc');

        $this->assertSame([10, 20, 30], $this->sourceValues($result, 'count'));
    }

    public function test_numeric_desc_orders_largest_first(): void
    {
        $hits   = $this->hits([30, 10, 20], 'count');
        $result = $this->sort($hits, 'tools', 'count:desc');

        $this->assertSame([30, 20, 10], $this->sourceValues($result, 'count'));
    }

    // -------------------------------------------------------------------------
    // Score passthrough
    // -------------------------------------------------------------------------

    public function test_score_desc_returns_hits_unchanged(): void
    {
        $hits   = $this->hits(['c', 'a', 'b'], 'name');
        $result = $this->sort($hits, 'datasets', 'score:desc');

        $this->assertSame(['c', 'a', 'b'], $this->sourceValues($result, 'name'));
    }

    public function test_score_asc_reverses_the_array(): void
    {
        $hits   = $this->hits(['c', 'a', 'b'], 'name');
        $result = $this->sort($hits, 'datasets', 'score:asc');

        $this->assertSame(['b', 'a', 'c'], $this->sourceValues($result, 'name'));
    }

    // -------------------------------------------------------------------------
    // Dataset title alias
    // -------------------------------------------------------------------------

    public function test_title_field_maps_to_short_title_for_datasets(): void
    {
        $hits = [
            ['_id' => '1', '_score' => 1.0, '_source' => ['shortTitle' => 'Zebra Study']],
            ['_id' => '2', '_score' => 1.0, '_source' => ['shortTitle' => 'Alpha Study']],
        ];

        $result = $this->sort($hits, 'datasets', 'title:asc');

        $this->assertSame('Alpha Study', $result[0]['_source']['shortTitle']);
        $this->assertSame('Zebra Study', $result[1]['_source']['shortTitle']);
    }

    public function test_title_field_is_not_aliased_for_non_dataset_types(): void
    {
        $hits = [
            ['_id' => '1', '_score' => 1.0, '_source' => ['title' => 'Zebra Tool']],
            ['_id' => '2', '_score' => 1.0, '_source' => ['title' => 'Alpha Tool']],
        ];

        $result = $this->sort($hits, 'tools', 'title:asc');

        $this->assertSame('Alpha Tool', $result[0]['_source']['title']);
    }
}
