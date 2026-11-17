<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Services\TypesenseService;
use Tests\Fakes\FakeTypesenseService;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class SimilarDatasetsV1Test extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private const V1_URL = '/api/v1/search/similar/datasets';

    private const V2_URL = '/api/v2/search/similar/datasets';

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
    }

    public function test_v1_returns_the_same_similar_datasets_as_v2(): void
    {
        $sourceDataset = $this->makeDataset('Asthma cohort', ['asthma', 'inhaler']);
        $this->indexDatasets([
            $sourceDataset,
            $this->makeDataset('Childhood asthma study', ['asthma']),
            $this->makeDataset('Inhaler prescribing data', ['inhaler']),
            $this->makeDataset('Diabetes audit', ['diabetes']),
        ]);

        $v1Response = $this->json('POST', self::V1_URL, ['id' => $sourceDataset['dataset_id']]);
        $v2Response = $this->json('POST', self::V2_URL, ['id' => $sourceDataset['dataset_id']]);

        $v1Response->assertStatus(200);
        $this->assertNotEmpty($v1Response->json('data'));
        $this->assertSame($v2Response->json(), $v1Response->json());
    }

    public function test_v1_tells_callers_it_is_deprecated_when_it_stops_and_what_replaces_it(): void
    {
        $this->indexDatasets([]);

        $response = $this->json('POST', self::V1_URL, ['id' => 1]);

        $response->assertStatus(200);
        $response->assertHeader('Deprecation', '@1794873600');
        $response->assertHeader('Sunset', 'Mon, 17 May 2027 23:59:59 GMT');
        $response->assertHeader('Link', '<' . self::V2_URL . '>; rel="successor-version"');
    }

    public function test_v2_is_not_marked_deprecated(): void
    {
        $this->indexDatasets([]);

        $response = $this->json('POST', self::V2_URL, ['id' => 1]);

        $response->assertStatus(200);
        $response->assertHeaderMissing('Deprecation');
        $response->assertHeaderMissing('Sunset');
    }

    /**
     * @param  string[]  $keywords
     * @return array<string, mixed> the dataset's Typesense document
     */
    private function makeDataset(string $title, array $keywords): array
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
            'partnerContext' => 'HDRUK',
        ];
    }

    private function indexDatasets(array $documents): void
    {
        $this->app->instance(
            TypesenseService::class,
            (new FakeTypesenseService())
                ->withDocuments((new DatasetVersion())->searchableAs(), $documents)
                ->withTextMatching()
        );
    }
}
