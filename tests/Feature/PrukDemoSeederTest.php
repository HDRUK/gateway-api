<?php

namespace Tests\Feature;

use App\Jobs\ExtractProjectGrantsFromMetadata;
use App\Jobs\ExtractPublicationsFromMetadata;
use App\Jobs\ExtractToolsFromMetadata;
use App\Jobs\LinkageExtraction;
use App\Jobs\SyncDatasetVariables;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasSpatialCoverage;
use App\Models\SpatialCoverage;
use App\Models\Team;
use Database\Seeders\Demo\PrukDemoCatalogue;
use Database\Seeders\PrukDemoSeeder;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PrukDemoSeederTest extends TestCase
{
    private const COUNT = 25;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['England', 'Northern Ireland', 'Scotland', 'Wales', 'Rest of the world'] as $region) {
            SpatialCoverage::firstOrCreate(['region' => $region]);
        }
    }

    private function seedDemo(int $count = self::COUNT): void
    {
        (new PrukDemoSeeder())->count($count)->run();
    }

    private function demoDatasets()
    {
        return Dataset::where('partner_context', 'PRUK')
            ->whereIn('team_id', Team::whereIn('name', $this->custodians())->pluck('id'));
    }

    private function custodians(): array
    {
        return array_merge(...array_values(PrukDemoCatalogue::CUSTODIANS));
    }

    public function test_it_seeds_active_pruk_datasets_owned_by_the_demo_custodians(): void
    {
        $this->seedDemo();

        $datasets = $this->demoDatasets()->get();
        $this->assertCount(self::COUNT, $datasets);
        $this->assertTrue($datasets->every(fn ($d) => $d->status === Dataset::STATUS_ACTIVE));

        $version = DatasetVersion::where('dataset_id', $datasets->first()->id)->sole();
        $this->assertSame(1, (int) $version->version);
        $this->assertSame('2.3', $version->gwdm_version);
        $this->assertSame('PRUK', $version->source_schema_model);
        $this->assertNotNull($version->active_date);
        $this->assertSame($version->title, $version->metadata['metadata']['summary']['title']);
    }

    public function test_running_it_again_updates_rather_than_duplicates(): void
    {
        $this->seedDemo();
        $this->seedDemo();

        $ids = $this->demoDatasets()->pluck('id');
        $this->assertCount(self::COUNT, $ids);
        $this->assertSame(self::COUNT, DatasetVersion::whereIn('dataset_id', $ids)->count());
        $this->assertSame(count(array_unique(array_column(
            (new \Database\Seeders\Demo\PrukDemoGenerator())->generate(self::COUNT),
            'custodian',
        ))), Team::whereIn('name', $this->custodians())->count());
    }

    public function test_a_larger_run_extends_a_smaller_one(): void
    {
        $this->seedDemo(10);
        $first = $this->demoDatasets()->orderBy('id')->pluck('pid');

        $this->seedDemo(self::COUNT);

        $this->assertCount(self::COUNT, $this->demoDatasets()->get());
        $this->assertEmpty($first->diff($this->demoDatasets()->pluck('pid')));
    }

    public function test_it_records_geography_so_the_location_filter_works(): void
    {
        $this->seedDemo();

        $regions = SpatialCoverage::pluck('region', 'id');
        foreach (DatasetVersion::whereIn('dataset_id', $this->demoDatasets()->pluck('id'))->get() as $version) {
            $spatial = $version->metadata['metadata']['coverage']['spatial'];
            $mapped = DatasetVersionHasSpatialCoverage::where('dataset_version_id', $version->id)
                ->pluck('spatial_coverage_id')
                ->map(fn ($id) => $regions[$id])
                ->sort()->values()->all();

            $expected = match ($spatial) {
                'United Kingdom' => ['England', 'Northern Ireland', 'Scotland', 'Wales'],
                'England and Wales' => ['England', 'Wales'],
                default => [$spatial],
            };
            $this->assertSame($expected, $mapped, $spatial);
        }
    }

    public function test_it_triggers_no_observer_jobs_so_indexing_is_a_single_reindex(): void
    {
        // Saving versions normally queues variable sync and metadata
        // extraction per dataset; across hundreds of datasets that's slow and
        // redone by the reindex anyway.
        Queue::fake();

        $this->seedDemo();

        foreach ([
            SyncDatasetVariables::class,
            ExtractPublicationsFromMetadata::class,
            ExtractToolsFromMetadata::class,
            ExtractProjectGrantsFromMetadata::class,
        ] as $job) {
            Queue::assertNotPushed($job);
        }
        // Its own linkage step, dispatched synchronously (the fake records
        // the sync connection too).
        Queue::assertPushed(LinkageExtraction::class, self::COUNT);
    }
}
