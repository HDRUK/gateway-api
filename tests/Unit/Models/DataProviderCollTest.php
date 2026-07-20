<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\DataProviderColl;

class DataProviderCollTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Regression: declared private $enabled/$name properties used to shadow
    // Eloquent's magic attribute access from inside the class, making
    // shouldBeSearchable() always false and toSearchableArray()'s 'enabled'/
    // 'name' always the property defaults, regardless of the real DB values.
    // -------------------------------------------------------------------------

    public function test_enabled_attribute_reflects_the_real_db_value(): void
    {
        $dataProviderColl = DataProviderColl::factory()->create(['enabled' => true]);

        $this->assertTrue($dataProviderColl->enabled);
    }

    public function test_name_attribute_reflects_the_real_db_value(): void
    {
        $dataProviderColl = DataProviderColl::factory()->create(['name' => 'Scottish Safe Haven Network']);

        $this->assertEquals('Scottish Safe Haven Network', $dataProviderColl->name);
    }

    public function test_shouldBeSearchable_is_true_when_enabled_and_not_deleted(): void
    {
        $dataProviderColl = DataProviderColl::factory()->create(['enabled' => true]);

        $this->assertTrue($dataProviderColl->shouldBeSearchable());
    }

    public function test_shouldBeSearchable_is_false_when_disabled(): void
    {
        $dataProviderColl = DataProviderColl::factory()->create(['enabled' => false]);

        $this->assertFalse($dataProviderColl->shouldBeSearchable());
    }

    public function test_shouldBeSearchable_is_false_when_deleted(): void
    {
        $dataProviderColl = DataProviderColl::factory()->create(['enabled' => true]);
        $dataProviderColl->delete();

        $this->assertFalse($dataProviderColl->fresh()->shouldBeSearchable());
    }

    public function test_scopeIndexEligible_matches_shouldBeSearchable(): void
    {
        $enabled = DataProviderColl::factory()->create(['enabled' => true]);
        $disabled = DataProviderColl::factory()->create(['enabled' => false]);

        $eligibleIds = DataProviderColl::indexEligible()->pluck('id');

        $this->assertTrue($eligibleIds->contains($enabled->id));
        $this->assertFalse($eligibleIds->contains($disabled->id));
    }

    public function test_toSearchableArray_reflects_real_enabled_and_name_values(): void
    {
        $dataProviderColl = DataProviderColl::factory()->create([
            'enabled' => true,
            'name' => 'Scottish Safe Haven Network',
        ]);

        $searchable = $dataProviderColl->toSearchableArray();

        $this->assertSame(1, $searchable['enabled']);
        $this->assertEquals('Scottish Safe Haven Network', $searchable['name']);
    }

    public function test_toSearchableArray_returns_empty_facets_when_no_teams_linked(): void
    {
        $dataProviderColl = DataProviderColl::factory()->create();

        $searchable = $dataProviderColl->toSearchableArray();

        $this->assertEquals([], $searchable['publisherNames']);
        $this->assertEquals([], $searchable['datasetTitles']);
    }
}
