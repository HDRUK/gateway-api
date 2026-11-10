<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Database\Seeders\CRUKDatasetVersionSeeder;
use Tests\TestCase;

/**
 * Seeded dataset versions carry a source schema that follows the same rules as
 * real ingestion, so the feature is visible in local and test data.
 */
class SeededSourceSchemaTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
    }

    private function versionFor(string $origin, string $partner = 'HDRUK'): DatasetVersion
    {
        $dataset = Dataset::factory()->create([
            'create_origin' => $origin,
            'partner_context' => $partner,
        ]);

        return DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);
    }

    public function test_factory_gives_onboarding_form_versions_the_form_s_schema(): void
    {
        $version = $this->versionFor(Dataset::ORIGIN_MANUAL);

        $this->assertSame(
            ['HDRUK', '4.1.0'],
            [$version->source_schema_model, $version->source_schema_version],
        );
    }

    public function test_factory_gives_gmi_versions_hdruk_with_no_version(): void
    {
        $version = $this->versionFor(Dataset::ORIGIN_GMI);

        $this->assertSame(
            ['HDRUK', null],
            [$version->source_schema_model, $version->source_schema_version],
        );
    }

    public function test_factory_gives_api_versions_a_declared_or_unknown_schema(): void
    {
        $version = $this->versionFor(Dataset::ORIGIN_API);

        $this->assertContains(
            [$version->source_schema_model, $version->source_schema_version],
            [['HDRUK', '4.1.0'], [null, null]],
        );
    }

    public function test_factory_follows_the_dataset_it_is_given_not_a_random_one(): void
    {
        // The seeder overrides dataset_id after the factory picks a random
        // dataset; the schema must follow the dataset actually assigned.
        $gmi = Dataset::factory()->create(['create_origin' => Dataset::ORIGIN_GMI]);

        for ($i = 0; $i < 5; $i++) {
            $version = DatasetVersion::factory()->create(['dataset_id' => $gmi->id]);
            $this->assertSame('HDRUK', $version->source_schema_model);
            $this->assertNull($version->source_schema_version);
        }
    }

    public function test_cruk_seeder_records_cruk_1_0_0_except_for_gmi(): void
    {
        Dataset::where('partner_context', 'CRUK')->delete();

        $manual = Dataset::factory()->create(['partner_context' => 'CRUK', 'create_origin' => Dataset::ORIGIN_MANUAL]);
        $gmi = Dataset::factory()->create(['partner_context' => 'CRUK', 'create_origin' => Dataset::ORIGIN_GMI]);

        $this->seed(CRUKDatasetVersionSeeder::class);

        $row = fn (Dataset $dataset) => DatasetVersion::where('dataset_id', $dataset->id)->first();

        // CRUK 1.0.0 is the only CRUK version TRASER can translate.
        $this->assertSame(['CRUK', '1.0.0'], [$row($manual)->source_schema_model, $row($manual)->source_schema_version]);
        // GMI pull ingestion is HDRUK-only.
        $this->assertSame(['HDRUK', null], [$row($gmi)->source_schema_model, $row($gmi)->source_schema_version]);
    }
}
