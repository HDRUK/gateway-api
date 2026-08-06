<?php

namespace Tests\Unit\SearchProviders;

use Config;
use Mockery;
use Tests\TestCase;
use App\Context\PartnerContext;
use App\SearchProviders\HDRUK;
use App\Models\DatasetVersion;
use App\Models\Tool;
use App\Models\DataProviderColl;
use App\Services\TypesenseService;
use Illuminate\Support\Facades\Http;
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

        // 'nonExistentField' has no matching schema entry on DatasetVersion.
        $this->assertNull($provider->collectionForFacetableFilter('dataset', 'nonExistentField'));
    }

    public function test_returns_collection_name_for_each_data_custodian_facet_field(): void
    {
        $provider = new HDRUK();
        $teamsCollection = (new \App\Models\Team())->searchableAs();

        foreach (['datasetTitles', 'dataType', 'geographicLocation'] as $field) {
            $this->assertEquals($teamsCollection, $provider->collectionForFacetableFilter('dataProvider', $field));
        }
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
    // collectionForFacetableFilter — tier-3 fields now in DatasetVersion
    // -------------------------------------------------------------------------

    public function test_returns_collection_for_collectionNames_filter_field(): void
    {
        $provider = new HDRUK();

        $this->assertEquals(
            (new DatasetVersion())->searchableAs(),
            $provider->collectionForFacetableFilter('dataset', 'collectionNames')
        );
    }

    public function test_returns_collection_for_dataProviderColl_filter_field(): void
    {
        $provider = new HDRUK();

        $this->assertEquals(
            (new DatasetVersion())->searchableAs(),
            $provider->collectionForFacetableFilter('dataset', 'dataProviderColl')
        );
    }

    public function test_returns_collection_for_dataUseTitles_filter_field(): void
    {
        $provider = new HDRUK();

        $this->assertEquals(
            (new DatasetVersion())->searchableAs(),
            $provider->collectionForFacetableFilter('dataset', 'dataUseTitles')
        );
    }

    /**
     * The old test comment said "not yet flattened" — that is now stale.
     * collectionNames (plural) IS a facet field; collectionName (singular,
     * no 's') is the wrong key and correctly returns null.
     */
    public function test_returns_null_for_collectionName_singular_which_is_not_a_facet_field(): void
    {
        $provider = new HDRUK();

        $this->assertNull($provider->collectionForFacetableFilter('dataset', 'collectionName'));
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
            !array_key_exists('filter_by', $searches[0]));

        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => [
                'tool' => [
                    'license' => ['MIT'],
                ],
            ],
        ]);
    }

    public function test_search_ignores_populationSize_filter_with_no_active_range(): void
    {
        // {includeUnreported: false} alone is the FE default state (slider not
        // moved). It must not produce a filter clause — doing so would silently
        // exclude the 600+ datasets where population size is not reported even
        // before the user has interacted with the slider.
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            !array_key_exists('filter_by', $searches[0]));

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

    /**
     * Regression: the real FE payload sends a literal JSON boolean inside the
     * array (isCohortDiscovery: [true]), not a pre-stringified "true".
     * normalizeFilterValues() used to only keep array elements that were
     * already strings, silently dropping a literal `true`/`false` and
     * leaving the search completely unfiltered.
     */
    public function test_search_builds_boolean_clause_from_a_literal_json_boolean_in_the_array(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            ($searches[0]['filter_by'] ?? null) === 'isCohortDiscovery:=true');

        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => ['dataset' => ['isCohortDiscovery' => [true]]],
        ]);
    }

    public function test_search_builds_false_clause_from_a_literal_json_boolean_in_the_array(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            ($searches[0]['filter_by'] ?? null) === 'isCohortDiscovery:=false');

        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => ['dataset' => ['isCohortDiscovery' => [false]]],
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

    // -------------------------------------------------------------------------
    // Checkbox-style boolean filters — sent as a flat top-level query param
    // (?isCohortDiscovery=isCohortDiscovery), not nested under `filters` like
    // the multi-select facets. Present (any value) means true; absent means
    // no filter at all.
    // -------------------------------------------------------------------------

    public function test_search_treats_flat_top_level_param_as_true_for_boolean_field(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            ($searches[0]['filter_by'] ?? null) === 'isCohortDiscovery:=true');

        (new HDRUK())->search('asthma', 'datasets', [
            'isCohortDiscovery' => 'isCohortDiscovery',
        ]);
    }

    public function test_search_combines_multiple_flat_boolean_params(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            ($searches[0]['filter_by'] ?? null) === 'containsBioSamples:=true && isCohortDiscovery:=true');

        (new HDRUK())->search('asthma', 'datasets', [
            'containsBioSamples' => 'containsBioSamples',
            'isCohortDiscovery' => 'isCohortDiscovery',
        ]);
    }

    public function test_search_applies_no_filter_when_flat_boolean_param_is_absent(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            !array_key_exists('filter_by', $searches[0]));

        (new HDRUK())->search('asthma', 'datasets', [
            'dataType' => '',
            'dataSubType' => '',
        ]);
    }

    /**
     * Regression: the FE's unchecked state isn't always a fully absent key —
     * it can be present with an empty string (?isCohortDiscovery=). An
     * earlier version of this fix checked array_key_exists() only, which
     * treated an empty-string "unchecked" param as true, incorrectly
     * combining it with any other genuinely-checked boolean filter.
     */
    public function test_search_treats_present_but_empty_flat_param_as_unchecked(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            ($searches[0]['filter_by'] ?? null) === 'containsBioSamples:=true');

        (new HDRUK())->search('asthma', 'datasets', [
            'isCohortDiscovery' => '',
            'containsBioSamples' => 'containsBioSamples',
        ]);
    }

    public function test_search_prefers_nested_filters_shape_over_flat_param_when_both_present(): void
    {
        // Nested (explicit array) takes precedence — the flat-param fallback
        // only kicks in when the nested lookup found nothing.
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            ($searches[0]['filter_by'] ?? null) === 'isCohortDiscovery:=false');

        (new HDRUK())->search('asthma', 'datasets', [
            'isCohortDiscovery' => 'isCohortDiscovery',
            'filters' => ['dataset' => ['isCohortDiscovery' => ['false']]],
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

    public function test_search_sets_a_drop_tokens_threshold_so_multi_term_queries_dont_stop_at_the_first_match(): void
    {
        // Typesense's default (1) stops widening a query as soon as ANY result is
        // found, so a query spanning two unrelated terms (e.g. pasted from two
        // different datasets' metadata) would only ever surface whichever term
        // matched best. Regression test for that behaviour.
        $this->mockTypesenseServiceExpectingSearches(
            fn ($searches) => ($searches[0]['drop_tokens_threshold'] ?? null) === 15
        );

        (new HDRUK())->search('term one, term two', 'datasets', []);
    }

    // -------------------------------------------------------------------------
    // Multi-select faceting — a field's own filter must not collapse its own
    // facet counts down to just the selected value(s).
    // -------------------------------------------------------------------------

    public function test_search_issues_an_unfiltered_exclusion_query_for_a_self_filtered_facet_field(): void
    {
        // Reproduces the reported bug: selecting "SAIL" for publisherName
        // must not shrink publisherName's own facet options down to SAIL.
        // 1 main + 1 exclusion for publisherName + 12 fixed extra (2 date range + 10 pop buckets) = 14
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            count($searches) === 14
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
            // 1 main + 2 exclusions (one per filtered field) + 12 fixed extra = 15
            if (count($searches) !== 15) {
                return false;
            }

            $byFacet = collect($searches)->skip(1)->take(2)->keyBy('facet_by');

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
        // 1 main + 1 exclusion + 12 fixed extra (2 date range + 10 pop buckets) = 14.
        // If dataType had wrongly also got an exclusion, count would be 15.
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) => count($searches) === 14);

        (new HDRUK())->search('asthma', 'datasets', [
            'filters' => ['dataset' => ['publisherName' => ['SAIL']]],
        ]);
    }

    // -------------------------------------------------------------------------
    // Date range filters — startDate/endDate carry a {from, to} object, not an
    // array of strings. The ordering bug in buildFilterClauses() meant these
    // were always silently dropped before the isDateRangeField check ran.
    // -------------------------------------------------------------------------

    public function test_search_builds_startDate_clause_from_to_bound(): void
    {
        // Overlap semantics: dataset covers query window when
        // dataset.startDate <= query.to
        $this->mockTypesenseServiceExpectingSearches(
            fn ($searches) =>
            str_contains($searches[0]['filter_by'] ?? '', 'startDate:<=')
        );

        (new HDRUK())->search('*', 'datasets', [
            'filters' => ['dataset' => ['startDate' => ['to' => '2023-12-31']]],
        ]);
    }

    public function test_search_builds_endDate_clause_from_from_bound(): void
    {
        // Overlap semantics: dataset covers query window when
        // dataset.endDate >= query.from
        $this->mockTypesenseServiceExpectingSearches(
            fn ($searches) =>
            str_contains($searches[0]['filter_by'] ?? '', 'endDate:>=')
        );

        (new HDRUK())->search('*', 'datasets', [
            'filters' => ['dataset' => ['endDate' => ['from' => '2020-01-01']]],
        ]);
    }

    public function test_search_includes_correct_timestamps_in_date_range_clauses(): void
    {
        $toTs   = strtotime('2023-12-31');
        $fromTs = strtotime('2020-01-01');

        $this->mockTypesenseServiceExpectingSearches(
            fn ($searches) =>
            ($searches[0]['filter_by'] ?? null) === "startDate:<={$toTs} && endDate:>={$fromTs}"
        );

        (new HDRUK())->search('*', 'datasets', [
            'filters' => ['dataset' => [
                'startDate' => ['to' => '2023-12-31'],
                'endDate'   => ['from' => '2020-01-01'],
            ]],
        ]);
    }

    public function test_search_ignores_startDate_filter_when_to_bound_is_absent(): void
    {
        // A {from} bound only makes no sense for startDate overlap — no clause.
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            !array_key_exists('filter_by', $searches[0]));

        (new HDRUK())->search('*', 'datasets', [
            'filters' => ['dataset' => ['startDate' => ['from' => '2020-01-01']]],
        ]);
    }

    public function test_search_ignores_endDate_filter_when_from_bound_is_absent(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            !array_key_exists('filter_by', $searches[0]));

        (new HDRUK())->search('*', 'datasets', [
            'filters' => ['dataset' => ['endDate' => ['to' => '2023-12-31']]],
        ]);
    }

    public function test_search_ignores_date_filter_when_value_is_not_an_array(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            !array_key_exists('filter_by', $searches[0]));

        (new HDRUK())->search('*', 'datasets', [
            'filters' => ['dataset' => ['startDate' => '2023-12-31']],
        ]);
    }

    public function test_search_ignores_date_filter_when_value_is_empty_array(): void
    {
        $this->mockTypesenseServiceExpectingSearches(fn ($searches) =>
            !array_key_exists('filter_by', $searches[0]));

        (new HDRUK())->search('*', 'datasets', [
            'filters' => ['dataset' => ['startDate' => []]],
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

    private function fakeSearchServiceDatasetsEndpoint(): void
    {
        Http::fake([
            config('gateway.search_service_url', 'http://localhost:8003') . '/search/datasets*' => Http::response([
                'hits' => ['total' => ['value' => 0], 'hits' => []],
                'aggregations' => [],
            ], 200),
            config('gateway.search_service_url', 'http://localhost:8003') . '/search/tools*' => Http::response([
                'hits' => ['total' => ['value' => 0], 'hits' => []],
                'aggregations' => [],
            ], 200),
        ]);
    }

    public function test_search_via_elastic_sends_partner_context_for_datasets(): void
    {
        $this->fakeSearchServiceDatasetsEndpoint();

        $partnerContext = Mockery::mock(PartnerContext::class);
        $partnerContext->shouldReceive('getPartner')->andReturn('PRUK');

        (new HDRUK($partnerContext))->search('asthma', 'datasets', []);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/search/datasets')
                && $request['partnerContext'] === 'PRUK';
        });
    }

    public function test_search_via_elastic_omits_partner_context_for_non_dataset_types(): void
    {
        $this->fakeSearchServiceDatasetsEndpoint();

        $partnerContext = Mockery::mock(PartnerContext::class);
        $partnerContext->shouldReceive('getPartner')->andReturn('PRUK');

        (new HDRUK($partnerContext))->search('nlp', 'tools', []);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/search/tools')
                && !array_key_exists('partnerContext', $request->data());
        });
    }

    public function test_search_via_elastic_respects_hdruk_cross_context_read_default(): void
    {
        $this->fakeSearchServiceDatasetsEndpoint();
        Config::set('partners.allow_cross_context_read', true);

        $partnerContext = Mockery::mock(PartnerContext::class);
        $partnerContext->shouldReceive('getPartner')->andReturn('HDRUK');

        (new HDRUK($partnerContext))->search('asthma', 'datasets', []);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/search/datasets')
                && array_key_exists('partnerContext', $request->data())
                && $request['partnerContext'] === null;
        });
    }

    public function test_hdruk_defaults_partner_context_when_constructed_without_argument(): void
    {
        $this->fakeSearchServiceDatasetsEndpoint();

        request()->headers->set('x-partner-context', 'CRUK');

        (new HDRUK())->search('asthma', 'datasets', []);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/search/datasets')
                && $request['partnerContext'] === 'CRUK';
        });
    }
}
