<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use App\Services\TypesenseService;
use Illuminate\Support\Facades\Http;
use Typesense\Client;
use Typesense\Collection;
use Typesense\Collections;
use Typesense\Document;
use Typesense\Documents;
use Typesense\Health;
use Typesense\Metrics;

class TypesenseServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Mock hierarchy builder
    // -------------------------------------------------------------------------

    /**
     * Builds a full Typesense client mock hierarchy and returns the service
     * wired to it, along with each mock for per-test configuration.
     *
     * @return array{service: TypesenseService, client: MockInterface, collections: MockInterface, collection: MockInterface, documents: MockInterface, document: MockInterface, health: MockInterface, metrics: MockInterface}
     */
    private function makeService(): array
    {
        $mockDocument = Mockery::mock(Document::class);
        $mockDocument->shouldReceive('retrieve')->byDefault()->andReturn(['id' => '1', 'title' => 'Test']);
        $mockDocument->shouldReceive('delete')->byDefault()->andReturn(['id' => '1']);

        $mockDocuments = Mockery::mock(Documents::class);
        $mockDocuments->shouldReceive('search')->byDefault()->andReturn(['found' => 0, 'hits' => []]);
        $mockDocuments->shouldReceive('upsert')->byDefault()->andReturn(['id' => '1']);
        $mockDocuments->shouldReceive('offsetGet')->byDefault()->andReturn($mockDocument);

        $mockCollection = Mockery::mock(Collection::class);
        $mockCollection->shouldReceive('retrieve')->byDefault()->andReturn(['name' => 'dataset_versions', 'num_documents' => 42]);
        $mockCollection->shouldReceive('delete')->byDefault()->andReturn(['name' => 'dataset_versions']);
        $mockCollection->documents = $mockDocuments;

        $mockCollections = Mockery::mock(Collections::class);
        $mockCollections->shouldReceive('retrieve')->byDefault()->andReturn([['name' => 'dataset_versions'], ['name' => 'tools']]);
        $mockCollections->shouldReceive('create')->byDefault()->andReturn(['name' => 'new_collection']);
        $mockCollections->shouldReceive('offsetGet')->byDefault()->andReturn($mockCollection);

        $mockHealth = Mockery::mock(Health::class);
        $mockHealth->shouldReceive('retrieve')->byDefault()->andReturn(['ok' => true]);

        $mockMetrics = Mockery::mock(Metrics::class);
        $mockMetrics->shouldReceive('retrieve')->byDefault()->andReturn(['latency_ms' => 1]);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->collections = $mockCollections;
        $mockClient->health      = $mockHealth;
        $mockClient->metrics     = $mockMetrics;

        $service = new TypesenseService($mockClient);

        return compact('service', 'mockClient', 'mockCollections', 'mockCollection', 'mockDocuments', 'mockDocument', 'mockHealth', 'mockMetrics');
    }

    // -------------------------------------------------------------------------
    // client()
    // -------------------------------------------------------------------------

    public function test_client_returns_the_underlying_typesense_client(): void
    {
        ['service' => $service, 'mockClient' => $mockClient] = $this->makeService();

        $this->assertSame($mockClient, $service->client());
    }

    // -------------------------------------------------------------------------
    // rawSearch
    // -------------------------------------------------------------------------

    public function test_rawSearch_passes_query_and_params_to_typesense(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections, 'mockCollection' => $mockCollection, 'mockDocuments' => $mockDocuments] = $this->makeService();

        $expected = ['found' => 1, 'hits' => [['document' => ['id' => '844']]]];
        $params   = ['query_by' => 'title,abstract', 'per_page' => 10];

        $mockCollections->shouldReceive('offsetGet')->with('dataset_versions')->andReturn($mockCollection);
        $mockDocuments->shouldReceive('search')
            ->once()
            ->with(array_merge(['q' => 'cancer'], $params))
            ->andReturn($expected);

        $result = $service->rawSearch('dataset_versions', 'cancer', $params);

        $this->assertEquals($expected, $result);
    }

    public function test_rawSearch_converts_empty_query_to_wildcard(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections, 'mockCollection' => $mockCollection, 'mockDocuments' => $mockDocuments] = $this->makeService();

        $mockCollections->shouldReceive('offsetGet')->with('dataset_versions')->andReturn($mockCollection);
        $mockDocuments->shouldReceive('search')
            ->once()
            ->with(['q' => '*'])
            ->andReturn(['found' => 1437, 'hits' => []]);

        $service->rawSearch('dataset_versions', '', []);
    }

    public function test_rawSearch_merges_params_after_q_so_caller_can_override(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections, 'mockCollection' => $mockCollection, 'mockDocuments' => $mockDocuments] = $this->makeService();

        $mockCollections->shouldReceive('offsetGet')->with('dataset_versions')->andReturn($mockCollection);
        $mockDocuments->shouldReceive('search')
            ->once()
            ->with(['q' => 'cancer', 'per_page' => 5])
            ->andReturn(['found' => 0, 'hits' => []]);

        $service->rawSearch('dataset_versions', 'cancer', ['per_page' => 5]);
    }

    // -------------------------------------------------------------------------
    // search (Scout delegation — unit-tests the wildcard conversion only;
    // full search behaviour is covered by Feature/V2/SearchAggregationTest)
    // -------------------------------------------------------------------------

    public function test_search_converts_empty_query_to_wildcard(): void
    {
        // Spy on the TypesenseService to assert the query reaching Scout is '*'.
        // We partially mock the service so we can intercept executeScoutSearch()
        // without pulling in a real Typesense engine.
        $mocks = $this->makeService();

        $service = Mockery::mock(TypesenseService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('executeScoutSearch')
            ->once()
            ->with(\Mockery::any(), '*', \Mockery::any())
            ->andReturn(['found' => 0, 'hits' => []]);

        $service->search('App\Models\DatasetVersion', '', []);
    }

    // -------------------------------------------------------------------------
    // Collection management
    // -------------------------------------------------------------------------

    public function test_listCollections_returns_all_collections(): void
    {
        ['service' => $service] = $this->makeService();

        $result = $service->listCollections();

        $this->assertCount(2, $result);
        $this->assertEquals('dataset_versions', $result[0]['name']);
        $this->assertEquals('tools', $result[1]['name']);
    }

    public function test_collectionExists_returns_true_when_collection_is_found(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections, 'mockCollection' => $mockCollection] = $this->makeService();

        $mockCollections->shouldReceive('offsetGet')->with('dataset_versions')->andReturn($mockCollection);
        $mockCollection->shouldReceive('retrieve')->once()->andReturn(['name' => 'dataset_versions']);

        $this->assertTrue($service->collectionExists('dataset_versions'));
    }

    public function test_collectionExists_returns_false_when_collection_is_not_found(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections] = $this->makeService();

        $mockCollections->shouldReceive('offsetGet')->with('missing')->andReturnUsing(function () {
            throw new \Exception('Not found', 404);
        });

        $this->assertFalse($service->collectionExists('missing'));
    }

    public function test_getCollection_returns_collection_info(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections, 'mockCollection' => $mockCollection] = $this->makeService();

        $expected = ['name' => 'dataset_versions', 'num_documents' => 1437, 'fields' => []];

        $mockCollections->shouldReceive('offsetGet')->with('dataset_versions')->andReturn($mockCollection);
        $mockCollection->shouldReceive('retrieve')->once()->andReturn($expected);

        $this->assertEquals($expected, $service->getCollection('dataset_versions'));
    }

    public function test_createCollection_passes_schema_to_typesense(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections] = $this->makeService();

        $schema = ['name' => 'new_collection', 'fields' => [['name' => 'id', 'type' => 'string']]];

        $mockCollections->shouldReceive('create')->once()->with($schema)->andReturn($schema);

        $result = $service->createCollection($schema);

        $this->assertEquals($schema, $result);
    }

    public function test_createCollectionFromModel_uses_model_schema(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections] = $this->makeService();

        $schema = ['name' => 'test_collection', 'fields' => [['name' => 'id', 'type' => 'string']]];

        $modelClass = new class () {
            public function typesenseCollectionSchema(): array
            {
                return ['name' => 'test_collection', 'fields' => [['name' => 'id', 'type' => 'string']]];
            }
        };

        $mockCollections->shouldReceive('create')->once()->with($schema)->andReturn($schema);

        $result = $service->createCollectionFromModel($modelClass::class);

        $this->assertEquals($schema, $result);
    }

    public function test_dropCollection_calls_delete_on_the_collection(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections, 'mockCollection' => $mockCollection] = $this->makeService();

        $mockCollections->shouldReceive('offsetGet')->with('dataset_versions')->andReturn($mockCollection);
        $mockCollection->shouldReceive('delete')->once()->andReturn(['name' => 'dataset_versions']);

        $result = $service->dropCollection('dataset_versions');

        $this->assertEquals(['name' => 'dataset_versions'], $result);
    }

    // -------------------------------------------------------------------------
    // Document operations
    // -------------------------------------------------------------------------

    public function test_upsertDocument_passes_document_to_typesense(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections, 'mockCollection' => $mockCollection, 'mockDocuments' => $mockDocuments] = $this->makeService();

        $document = ['id' => '42', 'title' => 'UK Biobank'];

        $mockCollections->shouldReceive('offsetGet')->with('dataset_versions')->andReturn($mockCollection);
        $mockDocuments->shouldReceive('upsert')->once()->with($document)->andReturn($document);

        $result = $service->upsertDocument('dataset_versions', $document);

        $this->assertEquals($document, $result);
    }

    public function test_deleteDocument_removes_document_by_id(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections, 'mockCollection' => $mockCollection, 'mockDocuments' => $mockDocuments, 'mockDocument' => $mockDocument] = $this->makeService();

        $mockCollections->shouldReceive('offsetGet')->with('dataset_versions')->andReturn($mockCollection);
        $mockDocuments->shouldReceive('offsetGet')->with('42')->andReturn($mockDocument);
        $mockDocument->shouldReceive('delete')->once()->andReturn(['id' => '42']);

        $result = $service->deleteDocument('dataset_versions', '42');

        $this->assertEquals(['id' => '42'], $result);
    }

    public function test_getDocument_retrieves_document_by_id(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections, 'mockCollection' => $mockCollection, 'mockDocuments' => $mockDocuments, 'mockDocument' => $mockDocument] = $this->makeService();

        $expected = ['id' => '844', 'title' => 'NHS GGC Diabetes'];

        $mockCollections->shouldReceive('offsetGet')->with('dataset_versions')->andReturn($mockCollection);
        $mockDocuments->shouldReceive('offsetGet')->with('844')->andReturn($mockDocument);
        $mockDocument->shouldReceive('retrieve')->once()->andReturn($expected);

        $result = $service->getDocument('dataset_versions', '844');

        $this->assertEquals($expected, $result);
    }

    public function test_documentCount_reads_num_documents_from_collection_info(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections, 'mockCollection' => $mockCollection] = $this->makeService();

        $mockCollections->shouldReceive('offsetGet')->with('dataset_versions')->andReturn($mockCollection);
        $mockCollection->shouldReceive('retrieve')->once()->andReturn(['name' => 'dataset_versions', 'num_documents' => 1437]);

        $this->assertEquals(1437, $service->documentCount('dataset_versions'));
    }

    public function test_documentCount_returns_zero_when_key_is_missing(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections, 'mockCollection' => $mockCollection] = $this->makeService();

        $mockCollections->shouldReceive('offsetGet')->with('dataset_versions')->andReturn($mockCollection);
        $mockCollection->shouldReceive('retrieve')->once()->andReturn(['name' => 'dataset_versions']);

        $this->assertEquals(0, $service->documentCount('dataset_versions'));
    }

    // -------------------------------------------------------------------------
    // facetCounts
    // -------------------------------------------------------------------------

    public function test_facetCounts_maps_typesense_facet_counts_to_es_bucket_shape(): void
    {
        ['service' => $service, 'mockCollections' => $mockCollections, 'mockCollection' => $mockCollection, 'mockDocuments' => $mockDocuments] = $this->makeService();

        $mockCollections->shouldReceive('offsetGet')->with('dataset_versions')->andReturn($mockCollection);
        $mockDocuments->shouldReceive('search')
            ->once()
            ->with([
                'q' => '*',
                'facet_by' => 'dataType',
                'per_page' => 0,
                'max_facet_values' => 250,
            ])
            ->andReturn([
                'facet_counts' => [
                    [
                        'field_name' => 'dataType',
                        'counts' => [
                            ['value' => 'Health and disease', 'count' => 1220],
                            ['value' => 'Registry', 'count' => 69],
                        ],
                    ],
                ],
            ]);

        $result = $service->facetCounts('dataset_versions', ['dataType']);

        $this->assertEquals([
            'dataType' => [
                'buckets' => [
                    ['key' => 'Health and disease', 'doc_count' => 1220],
                    ['key' => 'Registry', 'doc_count' => 69],
                ],
            ],
        ], $result);
    }

    public function test_facetCounts_returns_empty_array_for_no_fields(): void
    {
        ['service' => $service] = $this->makeService();

        $this->assertEquals([], $service->facetCounts('dataset_versions', []));
    }

    // -------------------------------------------------------------------------
    // Stop word sets
    // -------------------------------------------------------------------------

    public function test_withStopwords_merges_default_set_id_before_options(): void
    {
        ['service' => $service] = $this->makeService();

        $result = $service->withStopwords(['query_by' => 'title']);

        $this->assertEquals(
            ['stopwords' => TypesenseService::DEFAULT_STOPWORDS_SET_ID, 'query_by' => 'title'],
            $result
        );
    }

    public function test_withStopwords_lets_caller_override_set_id(): void
    {
        ['service' => $service] = $this->makeService();

        $result = $service->withStopwords(['stopwords' => 'custom_set']);

        $this->assertEquals(['stopwords' => 'custom_set'], $result);
    }

    public function test_upsertStopwordsSet_puts_stopwords_and_locale_to_the_rest_api(): void
    {
        ['service' => $service] = $this->makeService();

        Http::fake([
            '*/stopwords/common_stopwords' => Http::response(['stopwords' => ['id' => 'common_stopwords']], 200),
        ]);

        $result = $service->upsertStopwordsSet('common_stopwords', ['a', 'the'], 'en');

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && str_ends_with($request->url(), '/stopwords/common_stopwords')
                && $request['stopwords'] === ['a', 'the']
                && $request['locale'] === 'en';
        });

        $this->assertEquals(['stopwords' => ['id' => 'common_stopwords']], $result);
    }

    public function test_createDefaultStopwordsSet_upserts_the_default_set_id_and_wordlist(): void
    {
        ['service' => $service] = $this->makeService();

        Http::fake([
            '*/stopwords/*' => Http::response(['stopwords' => ['id' => TypesenseService::DEFAULT_STOPWORDS_SET_ID]], 200),
        ]);

        $service->createDefaultStopwordsSet();

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/stopwords/' . TypesenseService::DEFAULT_STOPWORDS_SET_ID)
                && $request['stopwords'] === TypesenseService::DEFAULT_STOPWORDS;
        });
    }

    public function test_getStopwordsSet_retrieves_a_named_set(): void
    {
        ['service' => $service] = $this->makeService();

        Http::fake([
            '*/stopwords/common_stopwords' => Http::response(['stopwords' => ['id' => 'common_stopwords', 'stopwords' => ['a']]], 200),
        ]);

        $result = $service->getStopwordsSet('common_stopwords');

        Http::assertSent(fn ($request) => $request->method() === 'GET');
        $this->assertEquals(['stopwords' => ['id' => 'common_stopwords', 'stopwords' => ['a']]], $result);
    }

    public function test_listStopwordsSets_retrieves_all_sets(): void
    {
        ['service' => $service] = $this->makeService();

        Http::fake([
            '*/stopwords' => Http::response(['stopwords' => []], 200),
        ]);

        $service->listStopwordsSets();

        Http::assertSent(fn ($request) => $request->method() === 'GET' && str_ends_with($request->url(), '/stopwords'));
    }

    public function test_deleteStopwordsSet_removes_a_named_set(): void
    {
        ['service' => $service] = $this->makeService();

        Http::fake([
            '*/stopwords/common_stopwords' => Http::response(['id' => 'common_stopwords'], 200),
        ]);

        $service->deleteStopwordsSet('common_stopwords');

        Http::assertSent(fn ($request) => $request->method() === 'DELETE');
    }

    // -------------------------------------------------------------------------
    // Health / diagnostics
    // -------------------------------------------------------------------------

    public function test_health_returns_typesense_health_status(): void
    {
        ['service' => $service, 'mockHealth' => $mockHealth] = $this->makeService();

        $mockHealth->shouldReceive('retrieve')->once()->andReturn(['ok' => true]);

        $this->assertEquals(['ok' => true], $service->health());
    }

    public function test_metrics_returns_typesense_metrics(): void
    {
        ['service' => $service, 'mockMetrics' => $mockMetrics] = $this->makeService();

        $mockMetrics->shouldReceive('retrieve')->once()->andReturn(['latency_ms' => 2]);

        $this->assertEquals(['latency_ms' => 2], $service->metrics());
    }
}
