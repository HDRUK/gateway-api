<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use App\Services\Gwdm\GwdmHandlerFactory;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * PR5 coverage — the app:backfill-gwdm30 command's selection + guard behaviour.
 *
 * Scoped to a single dataset via --dataset-id so seeded fixtures don't skew the
 * assertions. Observers disabled (the command's writes would otherwise dispatch
 * Elasticsearch jobs unrelated to the backfill).
 *
 * The translate -> prepareMetadata -> afterStore persist path itself is covered by
 * DatasetVersionGwdm30Test (PR4); a live 2.x->3.0 TRASER translation is not
 * reproducible through the shared test mock, so this suite exercises the parts
 * that do not depend on it: the dry-run guard and the "already at 3.0" skip
 * (idempotency) selection.
 */
class BackfillGwdm30CommandTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
        $this->disableObservers();
    }

    private function gwdm30Metadata(): array
    {
        return json_decode(
            file_get_contents(getcwd().'/tests/Unit/test_files/gwdm_v3p0_dataset_min.json'),
            true,
        )['metadata'];
    }

    /** Create an ACTIVE 2.0 dataset (single v1 snapshot) via the service. */
    private function createActive2xDataset(): int
    {
        $team = Team::first();
        $user = User::first();

        request()->headers->set('x-gwdm-version', '2.0');

        $created = app(DatasetService::class)->create(
            [
                'metadata' => $this->getMetadataV2p0(),
                'status' => Dataset::STATUS_ACTIVE,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'create_origin' => Dataset::ORIGIN_MANUAL,
            ],
            $team,
            null,
            null,
            false,
        );

        return $created['dataset_id'];
    }

    /** Create an ACTIVE dataset that already has a GWDM 3.0 version row. */
    private function createDatasetWith30Version(): int
    {
        $team = Team::first();
        $user = User::first();

        $dataset = Dataset::create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'pid' => 'bf-'.fake()->uuid(),
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'gwdm_version' => '3.0',
            'short_title' => 'Already 3.0',
            'metadata' => [
                'gwdmVersion' => '3.0',
                'metadata' => $this->gwdm30Metadata(),
                'original_metadata' => [],
            ],
        ]);

        return $dataset->id;
    }

    private function count30(int $datasetId): int
    {
        return DatasetVersion::where('dataset_id', $datasetId)
            ->where('gwdm_version', '3.0')
            ->count();
    }

    /**
     * An ACTIVE 3.0 dataset with SQL section rows populated but NO denormalised
     * section cache in the metadata column — i.e. a row written before the cache
     * existed. This is what --rebuild-json targets.
     */
    private function createUnbackfilled30Dataset(): int
    {
        $team = Team::first();
        $user = User::first();

        $dataset = Dataset::create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'pid' => 'bf-'.fake()->uuid(),
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $dv = DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'gwdm_version' => '3.0',
            'short_title' => 'Unbackfilled 3.0',
            'metadata' => ['gwdmVersion' => '3.0', 'original_metadata' => []],
        ]);

        // Write the SQL section tables (afterStore also refreshes the cache), then
        // strip the cache back out to simulate a pre-cache row.
        app(GwdmHandlerFactory::class)->resolve('3.0')->afterStore($dataset, $dv, $this->gwdm30Metadata());
        $dv->metadata = ['gwdmVersion' => '3.0', 'original_metadata' => []];
        $dv->saveQuietly();

        return $dataset->id;
    }

    public function test_rebuild_json_populates_section_cache_from_sql(): void
    {
        $datasetId = $this->createUnbackfilled30Dataset();

        $dv = DatasetVersion::where('dataset_id', $datasetId)->where('gwdm_version', '3.0')->first();
        $this->assertArrayNotHasKey('metadata', $dv->metadata, 'precondition: no section cache present');

        Artisan::call('app:backfill-gwdm30', ['--dataset-id' => $datasetId, '--rebuild-json' => true]);

        $dv->refresh();
        $this->assertArrayHasKey('metadata', $dv->metadata, 'rebuild-json should populate the section cache');
        $this->assertArrayHasKey('summary', $dv->metadata['metadata']);
        $this->assertArrayNotHasKey('linkage', $dv->metadata['metadata'], 'linkage stays out of the cache');

        // The cache equals the SQL reconstruction of the sections (no X-Gwdm-Cache
        // header, so the reconstruction reads from SQL — the default).
        $sql = app(DatasetService::class)->getReconstructedMetadataEnvelope(
            $datasetId,
            $dv->version,
            validate: false,
            prefetched: DatasetVersion::find($dv->id),
        );
        $this->assertEquals($sql['metadata']['summary'], $dv->metadata['metadata']['summary']);
    }

    public function test_rebuild_json_dry_run_writes_nothing(): void
    {
        $datasetId = $this->createUnbackfilled30Dataset();

        Artisan::call('app:backfill-gwdm30', [
            '--dataset-id' => $datasetId,
            '--rebuild-json' => true,
            '--dry-run' => true,
        ]);

        $dv = DatasetVersion::where('dataset_id', $datasetId)->where('gwdm_version', '3.0')->first();
        $this->assertArrayNotHasKey('metadata', $dv->metadata, 'dry-run must not write the cache');
    }

    public function test_rebuild_json_is_idempotent(): void
    {
        $datasetId = $this->createUnbackfilled30Dataset();

        Artisan::call('app:backfill-gwdm30', ['--dataset-id' => $datasetId, '--rebuild-json' => true]);
        $first = DatasetVersion::where('dataset_id', $datasetId)->where('gwdm_version', '3.0')->first()->metadata;

        Artisan::call('app:backfill-gwdm30', ['--dataset-id' => $datasetId, '--rebuild-json' => true]);
        $second = DatasetVersion::where('dataset_id', $datasetId)->where('gwdm_version', '3.0')->first()->metadata;

        $this->assertEquals($first, $second, 'a second rebuild-json must produce identical output');
    }

    public function test_dry_run_creates_no_gwdm30_version(): void
    {
        $datasetId = $this->createActive2xDataset();
        $this->assertSame(0, $this->count30($datasetId));

        Artisan::call('app:backfill-gwdm30', ['--dataset-id' => $datasetId, '--dry-run' => true]);

        $this->assertSame(0, $this->count30($datasetId), 'dry-run must not create a 3.0 version');
    }

    public function test_dataset_already_at_gwdm30_is_skipped_without_force(): void
    {
        $datasetId = $this->createDatasetWith30Version();
        $this->assertSame(1, $this->count30($datasetId));

        Artisan::call('app:backfill-gwdm30', ['--dataset-id' => $datasetId]);
        $output = Artisan::output();

        // Idempotency: a dataset that already has a 3.0 version is not re-migrated.
        $this->assertSame(1, $this->count30($datasetId), 'must not add a second 3.0 version');
        $this->assertStringContainsString('already', strtolower($output));
    }

    public function test_no_active_datasets_in_range_is_a_noop(): void
    {
        // An id-range that matches nothing exits cleanly without writing.
        Artisan::call('app:backfill-gwdm30', [
            '--min-dataset-id' => 999999,
            '--max-dataset-id' => 999999,
        ]);

        $this->assertStringContainsString(
            'Nothing to do',
            Artisan::output(),
        );
    }
}
