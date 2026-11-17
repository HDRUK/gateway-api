<?php

namespace Tests\Feature\V2;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Dur;
use App\Models\Team;
use App\Models\Tool;
use App\Services\TypesenseService;
use Tests\Fakes\FakeTypesenseService;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;
use Typesense\Exceptions\ServiceUnavailable;

class SearchExportTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private const EXPORT_LIMIT = 100;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
    }

    public function test_exports_the_matching_datasets_as_a_list(): void
    {
        $this->indexDatasets([
            $this->makeDataset('Asthma cohort', 'Publisher A'),
        ]);

        $rows = $this->exportCsv('datasets', ['download_type' => 'list']);

        $this->assertSame('Metadata title', $rows[0][0]);
        $this->assertSame(['Asthma cohort'], array_column(array_slice($rows, 1), 0));
    }

    public function test_exports_the_matching_datasets_as_a_table(): void
    {
        $this->indexDatasets([
            $this->makeDataset('Asthma cohort', 'Publisher A'),
        ]);

        $rows = $this->exportCsv('datasets', ['download_type' => 'table']);

        $this->assertSame('Metadata Title', $rows[0][0]);
        $this->assertContains('Cohort Discovery', $rows[0]);
        $this->assertSame(['Asthma cohort'], array_column(array_slice($rows, 1), 0));
    }

    public function test_exports_only_the_datasets_matching_the_active_filters(): void
    {
        $this->indexDatasets([
            $this->makeDataset('Asthma cohort', 'Publisher A'),
            $this->makeDataset('Diabetes registry', 'Publisher B'),
        ]);

        $rows = $this->exportCsv('datasets', [
            'download_type' => 'list',
            'filters'       => ['dataset' => ['publisherName' => ['Publisher B']]],
        ]);

        $this->assertSame(['Diabetes registry'], array_column(array_slice($rows, 1), 0));
    }

    public function test_exports_the_matching_data_uses(): void
    {
        $dur = Dur::create([
            'user_id'       => $this->currentUser['id'],
            'team_id'       => Team::factory()->create()->id,
            'project_title' => 'Asthma outcomes study',
            'status'        => Dur::STATUS_ACTIVE,
        ]);
        $this->indexDocuments($dur->searchableAs(), [$dur->toSearchableArray()]);

        $rows = $this->exportCsv('dur');

        $this->assertSame('Project Title', $rows[0][0]);
        $this->assertSame(['Asthma outcomes study'], array_column(array_slice($rows, 1), 0));
    }

    public function test_exports_at_most_the_first_hundred_tools(): void
    {
        $tools = Tool::factory()->count(self::EXPORT_LIMIT + 1)->create();
        $this->indexDocuments(
            (new Tool())->searchableAs(),
            $tools->map(fn (Tool $tool) => $tool->toSearchableArray())->all()
        );

        $rows = $this->exportCsv('tools');

        $this->assertSame('Tool Name', $rows[0][0]);
        $this->assertCount(self::EXPORT_LIMIT, array_slice($rows, 1));
    }

    public function test_fails_rather_than_exporting_an_empty_file_when_typesense_is_down(): void
    {
        $this->app->instance(TypesenseService::class, new class () extends FakeTypesenseService {
            public function rawSearch(string $collection, string $query, array $params = []): array
            {
                throw new ServiceUnavailable('Typesense is down');
            }

            public function multiSearch(array $searches): array
            {
                throw new ServiceUnavailable('Typesense is down');
            }
        });

        $response = $this->json('POST', '/api/v2/search/datasets/export', ['download_type' => 'list'], $this->header);

        $response->assertStatus(500);
    }

    public function test_does_not_export_types_the_search_page_cannot_download(): void
    {
        $this->indexDocuments((new DatasetVersion())->searchableAs(), []);

        $response = $this->json('POST', '/api/v2/search/collections/export', [], $this->header);

        $response->assertStatus(404);
    }

    public function test_rejects_an_unknown_dataset_download_type(): void
    {
        $this->indexDocuments((new DatasetVersion())->searchableAs(), []);

        $response = $this->json('POST', '/api/v2/search/datasets/export', ['download_type' => 'spreadsheet'], $this->header);

        $response->assertStatus(400);
    }

    /**
     * @return array<int, array<int, string|null>> CSV rows, headings first
     */
    private function exportCsv(string $type, array $body = []): array
    {
        $response = $this->json('POST', "/api/v2/search/{$type}/export", $body, $this->header);

        $response->assertStatus(200);
        $csv = trim($response->baseResponse->getFile()->getContent());

        return array_map('str_getcsv', explode("\n", $csv));
    }

    /**
     * @return array<string, mixed> the dataset's Typesense document
     */
    private function makeDataset(string $title, string $publisher): array
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
            'dataset_id'     => $dataset->id,
            'title'          => $title,
            'publisherName'  => $publisher,
            'partnerContext' => 'HDRUK',
        ];
    }

    private function indexDatasets(array $documents): void
    {
        $this->indexDocuments((new DatasetVersion())->searchableAs(), $documents);
    }

    private function indexDocuments(string $collection, array $documents): void
    {
        $this->app->instance(
            TypesenseService::class,
            (new FakeTypesenseService())->withDocuments($collection, $documents)
        );
    }
}
