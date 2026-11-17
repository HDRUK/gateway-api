<?php

namespace Tests\Feature\V2;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Services\TypesenseService;
use Laravel\Pennant\Feature;
use Tests\Fakes\FakeTypesenseService;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;
use Typesense\Exceptions\ServiceUnavailable;

class SimilarDatasetsTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private const URL = '/api/v2/search/similar/datasets';

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
    }

    public function test_never_returns_the_source_dataset_as_similar_to_itself(): void
    {
        $sourceDataset = $this->makeDataset('Asthma cohort', ['asthma']);
        $otherDataset = $this->makeDataset('Asthma registry', ['asthma']);
        $this->indexDatasets([$sourceDataset, $otherDataset]);

        $response = $this->json('POST', self::URL, ['id' => $sourceDataset['dataset_id']]);

        $response->assertStatus(200);
        $this->assertSame(
            [(int) $otherDataset['dataset_id']],
            array_map('intval', array_column($response->json('data'), '_id'))
        );
    }

    public function test_ranks_datasets_sharing_more_of_the_source_terms_first(): void
    {
        $sourceDataset = $this->makeDataset('Asthma cohort', ['asthma', 'inhaler', 'respiratory']);
        $twoTermDataset = $this->makeDataset('Inhaler audit', ['inhaler', 'respiratory']);
        $asthmaDataset = $this->makeDataset('Childhood asthma study', ['paediatrics']);
        $respiratoryDataset = $this->makeDataset('COPD registry', ['respiratory']);
        $unrelatedDataset = $this->makeDataset('Diabetes audit', ['diabetes']);
        $this->indexDatasets(
            [$unrelatedDataset, $respiratoryDataset, $asthmaDataset, $twoTermDataset, $sourceDataset],
            textMatching: true
        );

        $response = $this->json('POST', self::URL, ['id' => $sourceDataset['dataset_id']]);

        $response->assertStatus(200);
        $this->assertSame(
            ['Inhaler audit', 'Childhood asthma study', 'COPD registry'],
            array_column(array_column($response->json('data'), '_source'), 'title')
        );
    }

    public function test_fails_rather_than_returning_partial_results_when_typesense_rejects_a_term_search(): void
    {
        $sourceDataset = $this->makeDataset('Asthma cohort', ['asthma']);
        $this->app->instance(TypesenseService::class, new class ($sourceDataset) extends FakeTypesenseService {
            public function __construct(private array $sourceDataset)
            {
                parent::__construct();
            }

            public function rawSearch(string $collection, string $query, array $params = []): array
            {
                return ['found' => 1, 'hits' => [['document' => $this->sourceDataset, 'text_match' => 1]]];
            }

            public function multiSearch(array $searches): array
            {
                return ['results' => [['code' => 404, 'error' => 'Could not find a field named `x` in the schema.']]];
            }
        });

        $response = $this->json('POST', self::URL, ['id' => $sourceDataset['dataset_id']]);

        $response->assertStatus(500);
    }

    public function test_does_not_return_datasets_from_a_partner_context_the_caller_cannot_read(): void
    {
        $sourceDataset = $this->makeDataset('Asthma cohort', ['asthma']);
        $readableDataset = $this->makeDataset('Asthma registry', ['asthma']);
        $hiddenPartnerDataset = $this->makeDataset('Asthma biobank', ['asthma'], 'PRUK');
        $this->indexDatasets([$sourceDataset, $readableDataset, $hiddenPartnerDataset]);

        $response = $this->json('POST', self::URL, ['id' => $sourceDataset['dataset_id']], ['x-partner-context' => 'HDRUK']);

        $response->assertStatus(200);
        $this->assertSame(['Asthma registry'], $this->titles($response->json('data')));
    }

    public function test_returns_datasets_from_a_partner_context_once_its_flag_is_on(): void
    {
        Feature::activate('RenderSchemaPRUK');
        $sourceDataset = $this->makeDataset('Asthma cohort', ['asthma']);
        $partnerDataset = $this->makeDataset('Asthma biobank', ['asthma'], 'PRUK');
        $this->indexDatasets([$sourceDataset, $partnerDataset]);

        $response = $this->json('POST', self::URL, ['id' => $sourceDataset['dataset_id']], ['x-partner-context' => 'HDRUK']);

        $response->assertStatus(200);
        $this->assertSame(['Asthma biobank'], $this->titles($response->json('data')));
    }

    public function test_a_partner_only_gets_datasets_from_its_own_context(): void
    {
        $sourceDataset = $this->makeDataset('Asthma biobank', ['asthma'], 'PRUK');
        $ownContextDataset = $this->makeDataset('Asthma cohort study', ['asthma'], 'PRUK');
        $hdrukDataset = $this->makeDataset('Asthma registry', ['asthma']);
        $this->indexDatasets([$sourceDataset, $ownContextDataset, $hdrukDataset]);

        $response = $this->json('POST', self::URL, ['id' => $sourceDataset['dataset_id']], ['x-partner-context' => 'PRUK']);

        $response->assertStatus(200);
        $this->assertSame(['Asthma cohort study'], $this->titles($response->json('data')));
    }

    public function test_treats_a_source_dataset_the_caller_cannot_read_as_not_indexed(): void
    {
        $hiddenSourceDataset = $this->makeDataset('Asthma biobank', ['asthma'], 'PRUK');
        $readableDataset = $this->makeDataset('Asthma registry', ['asthma']);
        $this->indexDatasets([$hiddenSourceDataset, $readableDataset]);

        $response = $this->json('POST', self::URL, ['id' => $hiddenSourceDataset['dataset_id']], ['x-partner-context' => 'HDRUK']);

        $response->assertStatus(200);
        $response->assertExactJson(['data' => []]);
    }

    public function test_returns_no_datasets_for_a_dataset_that_is_not_indexed(): void
    {
        $indexedDataset = $this->makeDataset('Asthma cohort', ['asthma']);
        $this->indexDatasets([$indexedDataset]);

        $response = $this->json('POST', self::URL, ['id' => $indexedDataset['dataset_id'] + 1]);

        $response->assertStatus(200);
        $response->assertExactJson(['data' => []]);
    }

    public function test_fails_rather_than_returning_no_datasets_when_typesense_is_down(): void
    {
        $this->app->instance(TypesenseService::class, new class () extends FakeTypesenseService {
            public function rawSearch(string $collection, string $query, array $params = []): array
            {
                throw new ServiceUnavailable('Typesense is down');
            }
        });

        $response = $this->json('POST', self::URL, ['id' => 1]);

        $response->assertStatus(500);
    }

    public function test_rejects_a_request_without_a_dataset_id(): void
    {
        $this->indexDatasets([]);

        $response = $this->json('POST', self::URL, []);

        $response->assertStatus(400);
    }

    public function test_returns_at_most_three_hydrated_similar_datasets(): void
    {
        $sourceDataset = $this->makeDataset('Asthma cohort', ['asthma']);
        $similarDatasets = array_map(
            fn (int $n) => $this->makeDataset("Asthma study {$n}", ['asthma']),
            range(1, 4)
        );
        $this->indexDatasets([$sourceDataset, ...$similarDatasets]);

        $response = $this->json('POST', self::URL, ['id' => $sourceDataset['dataset_id']]);

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['_id', '_source' => ['title', 'publisherName'], 'metadata'],
            ],
        ]);
    }

    /**
     * @param  string[]  $keywords
     * @return array<string, mixed> the dataset's Typesense document
     */
    private function makeDataset(string $title, array $keywords, string $partnerContext = 'HDRUK'): array
    {
        $dataset = Dataset::factory()->create([
            'team_id' => Team::factory()->create()->id,
            'user_id' => 1,
            'status'  => Dataset::STATUS_ACTIVE,
        ]);

        $metadata = $this->getMetadata()['metadata'];
        $metadata['summary']['title'] = $title;

        $version = DatasetVersion::factory()->create([
            'dataset_id' => $dataset->id,
            'version'    => 1,
            'metadata'   => ['metadata' => $metadata, 'gwdmVersion' => '2.2'],
        ]);

        return [
            'id'             => (string) $version->id,
            'dataset_id'     => (string) $dataset->id,
            'title'          => $title,
            'keywords'       => $keywords,
            'publisherName'  => 'Publisher A',
            'partnerContext' => $partnerContext,
        ];
    }

    /** @return string[] */
    private function titles(array $data): array
    {
        return array_column(array_column($data, '_source'), 'title');
    }

    private function indexDatasets(array $documents, bool $textMatching = false): void
    {
        $typesense = (new FakeTypesenseService())->withDocuments((new DatasetVersion())->searchableAs(), $documents);

        $this->app->instance(TypesenseService::class, $textMatching ? $typesense->withTextMatching() : $typesense);
    }
}
