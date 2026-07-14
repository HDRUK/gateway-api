<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PR1 coverage for the queryable gwdm_version column on dataset_versions
 * (migration 2026_06_17_000001) and the reduced-relation selectRaw change on
 * DatasetVersion that now reads the title column directly rather than extracting
 * it from the JSON metadata envelope.
 */
class DatasetVersionGwdmVersionColumnTest extends TestCase
{
    private function seededDatasetId(): int
    {
        return Dataset::query()->firstOrFail()->id;
    }

    public function test_gwdm_version_column_exists_and_defaults_to_2_0(): void
    {
        $this->assertTrue(Schema::hasColumn('dataset_versions', 'gwdm_version'));

        $dv = DatasetVersion::withoutEvents(fn () => DatasetVersion::create([
            'dataset_id' => $this->seededDatasetId(),
            'version' => 1,
            'metadata' => json_encode(['gwdmVersion' => '2.0']),
        ]));

        // No explicit gwdm_version supplied -> the column default applies.
        $this->assertSame('2.0', $dv->fresh()->gwdm_version);
    }

    public function test_gwdm_version_is_mass_assignable(): void
    {
        $dv = DatasetVersion::withoutEvents(fn () => DatasetVersion::create([
            'dataset_id' => $this->seededDatasetId(),
            'version' => 1,
            'gwdm_version' => '2.1',
            'metadata' => json_encode(['gwdmVersion' => '2.1']),
        ]));

        $this->assertSame('2.1', $dv->fresh()->gwdm_version);
    }

    public function test_reduced_linked_dataset_versions_reads_title_column_for_delta_row(): void
    {
        $datasetId = $this->seededDatasetId();

        [$source, $target] = DatasetVersion::withoutEvents(function () use ($datasetId) {
            $source = DatasetVersion::create([
                'dataset_id' => $datasetId,
                'version' => 101,
                'metadata' => json_encode(['gwdmVersion' => '2.0']),
                'title' => 'Source Title',
                'short_title' => 'ST',
            ]);

            // Delta row: patch present, title lives only in the column (not in the
            // reduced metadata envelope). The selectRaw must still surface it.
            $target = DatasetVersion::create([
                'dataset_id' => $datasetId,
                'version' => 102,
                'patch' => json_encode([]),
                'metadata' => json_encode(['gwdmVersion' => '2.0']),
                'title' => 'Target Title',
                'short_title' => 'TT',
            ]);

            return [$source, $target];
        });

        DB::table('dataset_version_has_dataset_version')->insert([
            'dataset_version_source_id' => $source->id,
            'dataset_version_target_id' => $target->id,
            'linkage_type' => 'isDerivedFrom',
            'direct_linkage' => true,
        ]);

        $linked = $source->reducedLinkedDatasetVersions()->first();

        $this->assertNotNull($linked);
        $this->assertSame('Target Title', $linked->title);
        $this->assertSame('TT', $linked->shortTitle);
    }

    public function test_gwdm_version_backfill_deployment_step_runs_once(): void
    {
        $step = '2026_07_14_140156_backfill_gwdm_version';

        $this->assertDatabaseMissing('deployment_steps', ['step' => $step]);

        // deploy:run executes the pending step and records it. On SQLite the
        // back-fill body no-ops (driver guard) but the step is still marked run.
        Artisan::call('deploy:run');
        $this->assertDatabaseHas('deployment_steps', ['step' => $step]);

        // Run-once: a second deploy:run does not re-execute it.
        Artisan::call('deploy:run');
        $this->assertStringContainsString('Nothing to run', Artisan::output());
    }
}
