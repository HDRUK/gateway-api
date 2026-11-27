<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\SpatialCoverage;
use Database\Seeders\PrukDemoSeeder;
use Tests\TestCase;

/**
 * POST /api/v2/datasets/compare: two datasets' metadata side by side, with a
 * similarity call for every field. Reads only, and scoped to the caller's
 * partner like any other dataset read.
 */
class DatasetComparisonTest extends TestCase
{
    private const URL = '/api/v2/datasets/compare';

    private const PRUK = ['x-partner-context' => 'PRUK'];

    /** @var int[] */
    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['England', 'Northern Ireland', 'Scotland', 'Wales', 'Rest of the world'] as $region) {
            SpatialCoverage::firstOrCreate(['region' => $region]);
        }

        (new PrukDemoSeeder())->count(6)->run();
        $this->ids = Dataset::where('partner_context', 'PRUK')->orderBy('id')->pluck('id')->all();
    }

    private function compare(array $ids, array $headers = self::PRUK)
    {
        return $this->postJson(self::URL, ['dataset_ids' => $ids], $headers);
    }

    public function test_it_returns_both_datasets_and_a_call_for_each_field(): void
    {
        [$a, $b] = $this->ids;

        $response = $this->compare([$a, $b])->assertOk()->assertJsonPath('message', 'success');
        $data = $response->json('data');

        $this->assertSame([$a, $b], array_column($data['datasets'], 'id'));
        $this->assertSame(
            Dataset::find($a)->latestVersion()->title,
            $data['datasets'][0]['title'],
        );
        $this->assertArrayHasKey('publisherName', $data['datasets'][0]);
        $this->assertArrayHasKey('gwdmVersion', $data['datasets'][0]);

        $this->assertNotEmpty($data['fields']);
        foreach ($data['fields'] as $field) {
            $this->assertCount(2, $field['values']);
            $this->assertCount(2, $field['calls']);
        }
        $this->assertNotEmpty($data['thresholds']);
    }

    public function test_the_datasets_carry_their_metadata_without_variables_so_a_client_can_render_them(): void
    {
        [$a, $b] = $this->ids;

        $data = $this->compare([$a, $b])->assertOk()->json('data');

        foreach ($data['datasets'] as $dataset) {
            $this->assertArrayHasKey('summary', $dataset['metadata']);
            // Variables are the other tab, and by far the largest part.
            $this->assertArrayNotHasKey('structuralMetadata', $dataset['metadata']);
        }
    }

    public function test_the_order_asked_for_is_the_order_returned(): void
    {
        [$a, $b] = $this->ids;

        $forward = $this->compare([$a, $b])->json('data');
        $reverse = $this->compare([$b, $a])->json('data');

        $this->assertSame([$b, $a], array_column($reverse['datasets'], 'id'));
        $this->assertSame($forward['fields']['summary.title']['values'], array_reverse($reverse['fields']['summary.title']['values']));
    }

    public function test_it_compares_a_datasets_latest_version_not_an_older_one(): void
    {
        [$a, $b] = $this->ids;

        $first = Dataset::find($a)->latestVersion();
        $metadata = $first->metadata;
        $metadata['metadata']['summary']['populationSize'] = 12345;
        DatasetVersion::withoutEvents(fn () => $first->replicate()->fill(['version' => 2, 'metadata' => $metadata])->save());

        $data = $this->compare([$a, $b])->assertOk()->json('data');

        $this->assertSame(12345, $data['fields']['summary.populationSize']['values'][0]);
    }

    // Validation failures are 400 INVALID_ARGUMENT across this API (BaseFormRequest).

    public function test_it_compares_several_datasets_against_the_first(): void
    {
        config(['comparison.max_datasets' => 4]);
        $four = array_slice($this->ids, 0, 4);

        $data = $this->compare($four)->assertOk()->json('data');

        $this->assertSame($four, array_column($data['datasets'], 'id'));
        $field = $data['fields']['summary.title'];
        $this->assertCount(4, $field['values']);
        $this->assertNull($field['calls'][0]);
        $this->assertCount(3, array_filter($field['calls']));
    }

    public function test_it_needs_at_least_two_datasets_and_no_more_than_the_configured_limit(): void
    {
        config(['comparison.max_datasets' => 3]);

        $this->compare([])->assertStatus(400);
        $this->compare([$this->ids[0]])->assertStatus(400);
        $this->compare(array_slice($this->ids, 0, 3))->assertOk();
        $this->compare(array_slice($this->ids, 0, 4))->assertStatus(400);
    }

    public function test_the_same_dataset_twice_is_rejected(): void
    {
        $this->compare([$this->ids[0], $this->ids[0]])->assertStatus(400);
    }

    public function test_ids_must_be_positive_integers(): void
    {
        $this->compare(['abc', 1])->assertStatus(400);
        $this->compare([0, 1])->assertStatus(400);
    }

    public function test_an_unknown_dataset_is_not_found_and_the_response_says_which(): void
    {
        $response = $this->compare([$this->ids[0], 999999])->assertNotFound();

        $this->assertStringContainsString('999999', $response->json('message'));
    }

    public function test_a_dataset_that_is_not_active_is_not_found(): void
    {
        Dataset::whereKey($this->ids[1])->update(['status' => Dataset::STATUS_ARCHIVED]);

        $this->compare([$this->ids[0], $this->ids[1]])->assertNotFound();
    }

    public function test_a_dataset_from_another_partner_is_not_found(): void
    {
        Dataset::whereKey($this->ids[1])->update(['partner_context' => 'CRUK']);

        $this->compare([$this->ids[0], $this->ids[1]])->assertNotFound();
    }
}
