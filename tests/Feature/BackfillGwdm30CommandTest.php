<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
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
