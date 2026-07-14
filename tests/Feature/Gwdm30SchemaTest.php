<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Gwdm30\Accessibility;
use App\Models\Gwdm30\StructuralColumn;
use App\Models\Gwdm30\StructuralTable;
use App\Models\Gwdm30\StructuralValue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PR3 coverage — GWDM 3.0 SQL persistence schema + models (no behaviour on its own).
 *
 * Proves the ~18 gwdm30 migrations apply cleanly (RefreshDatabaseLite runs migrate
 * in setUp, so reaching any assertion already confirms this) and that the Eloquent
 * models — table names, $fillable, casts, and the hierarchical
 * StructuralTable -> StructuralColumn -> StructuralValue FK chain — are wired up.
 */
class Gwdm30SchemaTest extends TestCase
{
    private const TABLES = [
        'gwdm30_accessibility', 'gwdm30_coverage', 'gwdm30_dataset_filters',
        'gwdm30_demographic_frequencies', 'gwdm30_distributions', 'gwdm30_erd',
        'gwdm30_linkage_meta', 'gwdm30_observations', 'gwdm30_omics',
        'gwdm30_project_grants', 'gwdm30_provenance', 'gwdm30_quality_annotations',
        'gwdm30_required', 'gwdm30_structural_columns', 'gwdm30_structural_tables',
        'gwdm30_structural_values', 'gwdm30_summary', 'gwdm30_tissues_sample_collections',
    ];

    private function datasetVersion(): DatasetVersion
    {
        $datasetId = Dataset::query()->firstOrFail()->id;

        return DatasetVersion::withoutEvents(fn () => DatasetVersion::create([
            'dataset_id' => $datasetId,
            'version' => 900,
            'metadata' => json_encode(['gwdmVersion' => '3.0']),
            'gwdm_version' => '3.0',
        ]));
    }

    public function test_all_gwdm30_tables_are_created_by_migrations(): void
    {
        foreach (self::TABLES as $table) {
            $this->assertTrue(Schema::hasTable($table), "expected gwdm30 table missing: {$table}");
        }
    }

    public function test_structural_metadata_fk_chain_persists_and_traverses(): void
    {
        $dv = $this->datasetVersion();

        $table = StructuralTable::create([
            'dataset_version_id' => $dv->id,
            'name' => 'person',
            'description' => 'People table',
        ]);
        $column = StructuralColumn::create([
            'gwdm30_structural_table_id' => $table->id,
            'name' => 'age',
            'data_type' => 'int',
            'description' => 'Age in years',
            'sensitive' => true,
        ]);
        StructuralValue::create([
            'gwdm30_structural_column_id' => $column->id,
            'name' => '18-24',
            'description' => 'young adult',
            'frequency' => 42,
        ]);

        $reloaded = StructuralTable::with('columns.values')->find($table->id);

        $this->assertSame('person', $reloaded->name);
        $this->assertSame($dv->id, $reloaded->datasetVersion->id);
        $this->assertCount(1, $reloaded->columns);
        // boolean + integer casts round-trip through the DB.
        $this->assertTrue($reloaded->columns->first()->sensitive);
        $this->assertCount(1, $reloaded->columns->first()->values);
        $this->assertSame(42, $reloaded->columns->first()->values->first()->frequency);
    }

    public function test_single_row_section_casts_json_arrays(): void
    {
        $dv = $this->datasetVersion();

        $acc = Accessibility::create([
            'dataset_version_id' => $dv->id,
            'access_rights' => ['registration'],
            'formats' => ['CSV', 'JSON'],
        ]);

        $fresh = Accessibility::find($acc->id);
        $this->assertSame(['registration'], $fresh->access_rights);
        $this->assertSame(['CSV', 'JSON'], $fresh->formats);
    }
}
