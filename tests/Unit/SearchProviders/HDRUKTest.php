<?php

namespace Tests\Unit\SearchProviders;

use Tests\TestCase;
use App\SearchProviders\HDRUK;
use App\Models\DatasetVersion;
use App\Models\Tool;

class HDRUKTest extends TestCase
{
    // -------------------------------------------------------------------------
    // collectionForFacetableFilter
    // -------------------------------------------------------------------------

    public function test_returns_collection_name_when_field_is_facet_enabled(): void
    {
        $provider = new HDRUK();

        $this->assertEquals(
            (new DatasetVersion())->searchableAs(),
            $provider->collectionForFacetableFilter('dataset', 'dataType')
        );
    }

    public function test_returns_null_when_field_exists_but_is_not_facet_enabled(): void
    {
        $provider = new HDRUK();

        // 'abstract' is a real DatasetVersion field but not marked facet:true.
        $this->assertNull($provider->collectionForFacetableFilter('dataset', 'abstract'));
    }

    public function test_returns_null_when_field_does_not_exist_on_the_model(): void
    {
        $provider = new HDRUK();

        $this->assertNull($provider->collectionForFacetableFilter('dataset', 'accessService'));
    }

    public function test_returns_null_for_filter_type_with_no_typesense_model(): void
    {
        $provider = new HDRUK();

        // 'dataProvider' filter type (Team) is owned by external providers.
        $this->assertNull($provider->collectionForFacetableFilter('dataProvider', 'dataType'));
    }

    public function test_returns_null_for_unknown_filter_type(): void
    {
        $provider = new HDRUK();

        $this->assertNull($provider->collectionForFacetableFilter('not_a_real_type', 'dataType'));
    }

    public function test_returns_collection_name_for_each_tool_facet_field(): void
    {
        $provider = new HDRUK();
        $toolsCollection = (new Tool())->searchableAs();

        foreach (['license', 'programmingLanguages', 'typeCategory'] as $field) {
            $this->assertEquals($toolsCollection, $provider->collectionForFacetableFilter('tool', $field));
        }
    }
}
