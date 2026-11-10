<?php

namespace Tests\Unit\Services\Gwdm;

use App\Services\Gwdm\SourceSchema;
use PHPUnit\Framework\TestCase;

/**
 * SourceSchema records the schema a submission was made in - its ingestion
 * format (HDRUK, CRUK, ...) - as distinct from the GWDM version it is stored as.
 */
class SourceSchemaTest extends TestCase
{
    public function test_explicit_declaration_wins_over_the_envelope(): void
    {
        $schema = SourceSchema::declared('CRUK', '1.0.0', [
            'schemaModel' => 'HDRUK',
            'schemaVersion' => '4.1.0',
        ]);

        $this->assertSame(['CRUK', '1.0.0'], [$schema->model, $schema->version]);
    }

    public function test_falls_back_to_the_envelope_declaration(): void
    {
        // The onboarding form declares its schema inside the metadata envelope.
        $schema = SourceSchema::declared(null, null, [
            'schemaModel' => 'HDRUK',
            'schemaVersion' => '4.1.0',
            'metadata' => [],
        ]);

        $this->assertSame(['HDRUK', '4.1.0'], [$schema->model, $schema->version]);
    }

    public function test_is_unknown_when_nothing_is_declared(): void
    {
        $schema = SourceSchema::declared(null, null, ['metadata' => []]);

        $this->assertNull($schema->model);
        $this->assertNull($schema->version);
        $this->assertNull($schema->toArray());
    }

    public function test_ignores_a_version_declared_without_a_model(): void
    {
        $schema = SourceSchema::declared(null, '4.1.0');

        $this->assertNull($schema->model);
        $this->assertNull($schema->version);
    }

    public function test_keeps_a_model_declared_without_a_version(): void
    {
        $schema = SourceSchema::declared('HDRUK', null);

        $this->assertSame(['HDRUK', null], [$schema->model, $schema->version]);
    }

    public function test_treats_blank_strings_as_undeclared(): void
    {
        $schema = SourceSchema::declared('', '', ['schemaModel' => ' ', 'schemaVersion' => '']);

        $this->assertNull($schema->toArray());
    }

    public function test_gmi_is_always_hdruk_with_an_undetected_version(): void
    {
        // GMI pull ingestion only accepts HDRUK; TRASER detects which version
        // but does not report it, so the version is not known.
        $schema = SourceSchema::gmi();

        $this->assertSame(['HDRUK', null], [$schema->model, $schema->version]);
    }

    public function test_maps_to_dataset_version_columns(): void
    {
        $this->assertSame(
            ['source_schema_model' => 'CRUK', 'source_schema_version' => '1.0.0'],
            SourceSchema::declared('CRUK', '1.0.0')->toColumns()
        );
        $this->assertSame(
            ['source_schema_model' => null, 'source_schema_version' => null],
            SourceSchema::declared(null, null)->toColumns()
        );
    }

    public function test_round_trips_through_a_dataset_version_row(): void
    {
        $schema = SourceSchema::fromColumns([
            'source_schema_model' => 'HDRUK',
            'source_schema_version' => '4.1.0',
        ]);

        $this->assertSame(['model' => 'HDRUK', 'version' => '4.1.0'], $schema->toArray());
    }
}
