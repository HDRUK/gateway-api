<?php

namespace Tests\Feature;

use App\Jobs\IndexDataset;
use App\Models\Dataset;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use App\Services\Gwdm\Gwdm2xHandler;
use App\Services\Gwdm\GwdmHandlerFactory;
use Mockery;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * Coverage for IndexElastic::isElasticIndexable(), the gate added so datasets
 * on a GWDM version that doesn't support Elasticsearch indexing get deindexed
 * rather than partially/incorrectly indexed (GwdmMetadataHandler::supportsElasticIndexing()).
 *
 * No handler currently returns false, so this test fakes the handler resolution
 * to exercise the gate itself.
 */
class IndexElasticGwdmGatingTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected function setUp(): void
    {
        $this->commonSetUp();
        $this->disableObservers();
    }

    public function test_reindex_elastic_deindexes_when_handler_does_not_support_elastic_indexing(): void
    {
        $team = Team::first();
        $user = User::first();
        $metadata = $this->getMetadataV2p0();

        request()->headers->set('x-gwdm-version', '2.0');
        $created = app(DatasetService::class)->create(
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

        // Fake a handler that overrides supportsElasticIndexing() to false.
        $fakeHandler = Mockery::mock(Gwdm2xHandler::class, ['2.0'])->makePartial();
        $fakeHandler->shouldReceive('supportsElasticIndexing')->andReturn(false);

        $fakeFactory = Mockery::mock(GwdmHandlerFactory::class)->makePartial();
        $fakeFactory->shouldReceive('resolve')->andReturn($fakeHandler);
        app()->instance(GwdmHandlerFactory::class, $fakeFactory);

        // Partial-mock so we can assert the deindex path actually fired.
        $job = Mockery::mock(IndexDataset::class, [(string) $datasetId])->makePartial();
        $job->shouldReceive('deleteDatasetFromElastic')
            ->once()
            ->with((string) $datasetId);

        $params = $job->reindexElastic((string) $datasetId, true);

        $this->assertNull($params, 'a non-indexable version must skip building an ES document');
    }

    public function test_reindex_elastic_still_indexes_when_handler_supports_elastic_indexing(): void
    {
        $team = Team::first();
        $user = User::first();
        $metadata = $this->getMetadataV2p0();

        request()->headers->set('x-gwdm-version', '2.0');
        $created = app(DatasetService::class)->create(
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

        $job = new IndexDataset((string) $datasetId);
        $params = $job->reindexElastic((string) $datasetId, true);

        $this->assertIsArray($params, 'an indexable version (real GWDM 2.0 today) must still build an ES document');
        $this->assertSame((int) $datasetId, $params['id']);
    }

    public function test_reindex_elastic_includes_the_dataset_partner_context(): void
    {
        $team = Team::first();
        $user = User::first();
        $metadata = $this->getMetadataV2p0();

        request()->headers->set('x-gwdm-version', '2.0');
        $created = app(DatasetService::class)->create(
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

        Dataset::where('id', $datasetId)->update(['partner_context' => 'CRUK']);

        $job = new IndexDataset((string) $datasetId);
        $params = $job->reindexElastic((string) $datasetId, true);

        $this->assertSame('CRUK', $params['body']['partnerContext']);
    }
}
