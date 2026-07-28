<?php

namespace Tests\Feature;

use App\Services\Gwdm\GwdmHandlerFactory;
use Tests\TestCase;

/**
 * getMaterialTypes() is version-specific and lives on the GWDM handlers (the
 * version_compare() that used to gate it in IndexElastic/DatasetHydrator was
 * removed). Legacy (< 1.1) exposes no material types; 1.1+ reads
 * tissuesSampleCollection.
 */
class GwdmHandlerMaterialTypesTest extends TestCase
{
    protected bool $shouldFakeQueue = false;

    private function factory(): GwdmHandlerFactory
    {
        return new GwdmHandlerFactory();
    }

    public function test_1x_handler_returns_null_even_when_tissues_present(): void
    {
        $result = $this->factory()->resolve('1.0')->getMaterialTypes([
            'tissuesSampleCollection' => [['materialType' => 'DNA']],
        ]);

        $this->assertNull($result);
    }

    public function test_2x_handler_extracts_dedupes_and_excludes_none(): void
    {
        $result = $this->factory()->resolve('2.1')->getMaterialTypes([
            'tissuesSampleCollection' => [
                ['materialType' => 'DNA'],
                ['materialType' => 'RNA'],
                ['materialType' => 'DNA'],
                ['materialType' => 'None/not available'],
            ],
        ]);

        $this->assertEqualsCanonicalizing(['DNA', 'RNA'], array_values($result));
    }

    public function test_2x_handler_returns_null_when_absent_or_all_excluded(): void
    {
        $handler = $this->factory()->resolve('2.0');

        $this->assertNull($handler->getMaterialTypes([]));
        $this->assertNull($handler->getMaterialTypes([
            'tissuesSampleCollection' => [['materialType' => 'None/not available']],
        ]));
    }

    public function test_material_types_boundary_follows_the_factory_at_1_1(): void
    {
        // A version in [1.1, 2.0) resolves to the 2.x handler, so it now uses the
        // modern tissuesSampleCollection path. This is a deliberate shift of the
        // effective boundary from 2.0 to 1.1 (the factory's split point).
        $result = $this->factory()->resolve('1.5')->getMaterialTypes([
            'tissuesSampleCollection' => [['materialType' => 'DNA']],
        ]);

        $this->assertSame(['DNA'], array_values($result));
    }
}
