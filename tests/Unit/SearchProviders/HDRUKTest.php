<?php

namespace Tests\Unit\SearchProviders;

use Mockery;
use Tests\TestCase;
use App\SearchProviders\HDRUK;
use App\Models\DatasetVersion;
use App\Models\Tool;
use App\Models\DataProviderColl;
use App\Services\TypesenseService;
use Laravel\Pennant\Feature;

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

        // collectionName is a real Filter row but is cross-entity (Collection
        // linked via a pivot) and not yet flattened into DatasetVersion.
        $this->assertNull($provider->collectionForFacetableFilter('dataset', 'collectionName'));
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

    /**
     * Regression: FILTER_TYPE_MAP previously mapped the 'data_custodian_networks'
     * service key to 'dataProviderColl', but the `filters` table (and FE
     * payloads) actually use the literal string 'datacustodiannetwork' —
     * meaning this could never have resolved. Confirmed against live DB rows.
     */
    public function test_returns_collection_name_for_each_data_custodian_network_facet_field(): void
    {
        $provider = new HDRUK();
        $collection = (new DataProviderColl())->searchableAs();

        foreach (['publisherNames', 'datasetTitles'] as $field) {
            $this->assertEquals($collection, $provider->collectionForFacetableFilter('datacustodiannetwork', $field));
        }
    }

    public function test_returns_null_for_the_old_incorrect_data_custodian_network_filter_type(): void
    {
        $provider = new HDRUK();

        $this->assertNull($provider->collectionForFacetableFilter('dataProviderColl', 'publisherNames'));
    }

    // -------------------------------------------------------------------------
    // search() — nested filters shape: filters: { [type]: { [field]: "a|b" } }
    // -------------------------------------------------------------------------

    public function tearDown(): void
    {
        Feature::flushCache();
        Feature::deactivate('TypesenseSearch');

        parent::tearDown();
    }

    /**
     * Mocks TypesenseService::multiSearch(), asserting on the full $searches
     * array passed to it. Returns as many empty result stubs as searches
     * were sent, so callers only asserting on request shape don't need to
     * hand-craft a matching response.
     */
    private function mockTypesenseServiceExpectingSearches(callable $searchesMatcher): void
    {
        $mockService = Mockery::mock(TypesenseService::class);
        $mockService->shouldReceive('multiSearch')
            ->once()
            ->with(Mockery::on($searchesMatcher))
            ->andReturnUsing(fn ($searches) => [
                'results' => array_fill(0, count($searches), ['found' => 0, 'hits' => [], 'facet_counts' => []]),
            ]);

        $this->app->instance(TypesenseService::class, $mockService);

        Feature::flushCache();
        Feature::activate('TypesenseSearch');
    }

    public function test_search_builds_filter_by_from_nested_filters_shape(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            ($searches[0]['filter_by'] ?? null) === 'publisherName:=[`Dementia Platform UK`,`International COVID-19 Data Alliance (ICODA)`]');

        (new HDRUK())->search('asthma', 'datasets', [
            'page' => 1,
            'per_page' => 20,
            // Nested under the singular Filter.type value ('dataset'), not
            // the plural service key ('datasets') used elsewhere in $type.
            'filters' => [
                'dataset' => [
                    'publisherName' => ['Dementia Platform UK', 'International COVID-19 Data Alliance (ICODA)'],
                ],
            ],
        ]);
    }

    public function test_search_ignores_filters_nested_under_a_different_type(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            count($searches) === 1 && !array_key_exists('filter_by', $searches[0]));

        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => [
                'tool' => [
                    'license' => ['MIT'],
                ],
            ],
        ]);
    }

    public function test_search_ignores_structured_filter_values_it_does_not_support_yet(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            count($searches) === 1 && !array_key_exists('filter_by', $searches[0]));

        // populationSize isn't in TYPESENSE_FILTERABLE_MAP yet, and even if it
        // were, its {includeUnreported} shape isn't an array of strings —
        // this must not throw, just produce no filter clause.
        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => [
                'dataset' => [
                    'populationSize' => ['includeUnreported' => false],
                    'sampleAvailability' => [],
                ],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Boolean facet fields — Typesense needs a bare `field:=true`, not the
    // quoted-array syntax used for string facets (which silently matches
    // zero rows against a bool field instead of erroring).
    // -------------------------------------------------------------------------

    public function test_search_builds_a_bare_unquoted_clause_for_boolean_facet_fields(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            ($searches[0]['filter_by'] ?? null) === 'containsBioSamples:=true');

        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => ['dataset' => ['containsBioSamples' => ['true']]],
        ]);
    }

    public function test_search_builds_false_boolean_clause_correctly(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            ($searches[0]['filter_by'] ?? null) === 'isCohortDiscovery:=false');

        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => ['dataset' => ['isCohortDiscovery' => ['false']]],
        ]);
    }

    public function test_search_ignores_boolean_filter_when_both_values_selected(): void
    {
        // Selecting both true and false is equivalent to no filter at all.
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            !array_key_exists('filter_by', $searches[0]));

        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => ['dataset' => ['isCohortDiscovery' => ['true', 'false']]],
        ]);
    }

    public function test_search_ignores_invalid_boolean_value(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            !array_key_exists('filter_by', $searches[0]));

        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => ['dataset' => ['isCohortDiscovery' => ['maybe']]],
        ]);
    }

    public function test_search_reads_per_page_from_request_params(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) => $searches[0]['per_page'] === 45);

        (new HDRUK())->search('asthma', 'datasets', ['per_page' => 45]);
    }

    public function test_search_defaults_per_page_to_20_when_absent(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) => $searches[0]['per_page'] === 20);

        (new HDRUK())->search('asthma', 'datasets', []);
    }

    // -------------------------------------------------------------------------
    // Multi-select faceting — a field's own filter must not collapse its own
    // facet counts down to just the selected value(s).
    // -------------------------------------------------------------------------

    public function test_search_issues_an_unfiltered_exclusion_query_for_a_self_filtered_facet_field(): void
    {
        // Reproduces the reported bug: selecting "SAIL" for publisherName
        // must not shrink publisherName's own facet options down to SAIL.
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            count($searches) === 2
            && ($searches[0]['filter_by'] ?? null) === 'publisherName:=[`SAIL`]'
            && ($searches[1]['facet_by'] ?? null) === 'publisherName'
            && ($searches[1]['per_page'] ?? null) === 0
            && !array_key_exists('filter_by', $searches[1]));

        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => ['dataset' => ['publisherName' => ['SAIL']]],
        ]);
    }

    public function test_search_exclusion_query_keeps_other_fields_filters(): void
    {
        // With two active filters, publisherName's exclusion query must keep
        // dataType's clause (and vice versa) — only the field's own clause
        // is dropped from its own exclusion query.
        $this->mockTypesenseServiceExpectingSearches(function ($searches) {
            if (count($searches) !== 3) {
                return false;
            }

            $byFacet = collect($searches)->skip(1)->keyBy('facet_by');

            return ($searches[0]['filter_by'] ?? null) === 'publisherName:=[`SAIL`] && dataType:=[`Registry`]'
                && ($byFacet['publisherName']['filter_by'] ?? null) === 'dataType:=[`Registry`]'
                && ($byFacet['dataType']['filter_by'] ?? null) === 'publisherName:=[`SAIL`]';
        });

        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => [
                'dataset' => [
                    'publisherName' => ['SAIL'],
                    'dataType' => ['Registry'],
                ],
            ],
        ]);
    }

    public function test_search_does_not_issue_exclusion_queries_for_unfiltered_facet_fields(): void
    {
        // dataType has no active filter of its own, so it needs no exclusion
        // query — only publisherName (the filtered field) does.
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) => count($searches) === 2);

        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => ['dataset' => ['publisherName' => ['SAIL']]],
        ]);
    }

    public function test_search_uses_exclusion_result_facet_counts_for_the_self_filtered_field(): void
    {
        $mockService = Mockery::mock(TypesenseService::class);
        $mockService->shouldReceive('multiSearch')
            ->once()
            ->andReturn([
                'results' => [
                    [
                        'found' => 7,
                        'hits' => [],
                        // Main (filtered) query only "sees" SAIL, since the
                        // result set itself is restricted to SAIL rows.
                        'facet_counts' => [
                            ['field_name' => 'publisherName', 'counts' => [
                                ['value' => 'SAIL', 'count' => 7],
                            ]],
                        ],
                    ],
                    [
                        // Exclusion query: every publisherName option, as if
                        // publisherName's own filter weren't applied.
                        'facet_counts' => [
                            ['field_name' => 'publisherName', 'counts' => [
                                ['value' => 'PIONEER: HDR UK Health Da...', 'count' => 16],
                                ['value' => 'SAIL', 'count' => 7],
                                ['value' => 'Tissue directory', 'count' => 2],
                            ]],
                        ],
                    ],
                ],
            ]);

        $this->app->instance(TypesenseService::class, $mockService);
        Feature::flushCache();
        Feature::activate('TypesenseSearch');

        $result = (new HDRUK())->search('asthma', 'datasets', [
            'filters' => ['dataset' => ['publisherName' => ['SAIL']]],
        ]);

        $this->assertEquals(
            ['PIONEER: HDR UK Health Da...' => 16, 'SAIL' => 7, 'Tissue directory' => 2],
            collect($result['aggregations']['publisherName']['buckets'])->pluck('doc_count', 'key')->all()
        );
    }
}
