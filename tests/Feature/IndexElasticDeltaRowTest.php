<?php

namespace Tests\Feature;

use App\Jobs\IndexDataset;
use App\Models\Collection;
use App\Models\CollectionHasDatasetVersion;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * Regression coverage for GAT-9018: IndexElastic::reindexElasticDataProvider()
 * and ::indexElasticCollections() used to read `$latestVersion->metadata`
 * directly. Delta rows store `metadata = []` there, so
 * `$metadata['metadata']['summary']['datasetType']` was null and
 * explode(';,;', null) threw a TypeError in PHP 8.1+ (deprecated-to-fatal).
 * Both methods now reconstruct via DatasetService::getReconstructedMetadataEnvelope(),
 * matching what IndexElastic::reindexElastic() already did.
 */
class IndexElasticDeltaRowTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
        $this->disableObservers();
    }

    private function setGwdmHeader(string $version): void
    {
        request()->headers->set('x-gwdm-version', $version);
    }

    private function service(): DatasetService
    {
        return app(DatasetService::class);
    }

    /**
     * @return array{0: int, 1: DatasetVersion} dataset id and the resulting delta-row version
     */
    private function createDatasetWithDeltaVersion(Team $team, User $user, array $metadata): array
    {
        $this->setGwdmHeader('2.0');

        $created = $this->service()->create(
            [
                'metadata' => $metadata,
                'status' => Dataset::STATUS_ACTIVE,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'create_origin' => Dataset::ORIGIN_MANUAL,
            ],
            $team,
            null,
            null,
            false,
        );
        $datasetId = $created['dataset_id'];

        // v2: identical schema -> delta row (metadata column stores `[]`).
        $this->service()->update(
            Dataset::find($datasetId),
            ['metadata' => $metadata, 'status' => Dataset::STATUS_ACTIVE],
            $user->id,
            $team->id,
            Dataset::ORIGIN_MANUAL,
            false,
            $team,
        );

        $v2 = DatasetVersion::where('dataset_id', $datasetId)->where('version', 2)->first();
        $this->assertNotNull($v2, 'v2 should have been created');
        $this->assertNotNull($v2->patch, 'v2 must be a delta row for this regression test to be meaningful');
        $this->assertSame([], $v2->metadata, 'delta row metadata column must be empty, confirming a raw-column read would crash/degrade');

        return [$datasetId, $v2];
    }

    public function test_reindex_elastic_data_provider_does_not_crash_on_delta_row(): void
    {
        $team = Team::first();
        $user = User::first();

        $metadata = $this->getMetadataV2p0();
        [, $v2] = $this->createDatasetWithDeltaVersion($team, $user, $metadata);

        $job = new IndexDataset('0');
        $params = $job->reindexElasticDataProvider((string) $team->id, true);

        $this->assertIsArray($params);
        $this->assertContains('list of papers', $params['body']['dataType']);
    }

    public function test_index_elastic_collections_does_not_crash_on_delta_row(): void
    {
        $team = Team::first();
        $user = User::first();

        $metadata = $this->getMetadataV2p0();
        [$datasetId, $v2] = $this->createDatasetWithDeltaVersion($team, $user, $metadata);

        $collection = Collection::factory()->create([
            'team_id' => $team->id,
            'status' => Collection::STATUS_ACTIVE,
        ]);

        CollectionHasDatasetVersion::create([
            'collection_id' => $collection->id,
            'dataset_version_id' => $v2->id,
            'user_id' => $user->id,
        ]);

        $job = new IndexDataset('0');
        $params = $job->indexElasticCollections($collection->id, true);

        $this->assertIsArray($params);
        $this->assertContains(
            'Publications that mention HDR-UK (or any variant thereof) in Acknowledgements or Author Affiliations',
            $params['body']['datasetAbstracts']
        );
    }
}
