<?php

namespace Tests\Feature\Observers;

use Mockery;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\ProjectGrant;
use App\Models\TeamHasUser;
use App\Models\ProjectGrantVersion;
use App\Models\ProjectGrantVersionHasDataset;
use Tests\Traits\MockExternalApis;
use App\Observers\ProjectGrantVersionObserver;

class ProjectGrantVersionObserverTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $metadata;
    private $observer;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        ProjectGrant::flushEventListeners();
        ProjectGrantVersion::flushEventListeners();

        $this->metadata = $this->getMetadata();

        $this->observer = Mockery::mock(ProjectGrantVersionObserver::class)->makePartial();
        app()->instance(ProjectGrantVersionObserver::class, $this->observer);
    }

    private function makeActiveDataset(): Dataset
    {
        $teamHasUser = TeamHasUser::query()->first();
        $dataset = Dataset::create([
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'provider_team_id' => $dataset->team_id,
            'version' => 1,
            'metadata' => $this->metadata,
        ]);

        return $dataset;
    }

    public function test_created_reindexes_linked_active_datasets(): void
    {
        $dataset = $this->makeActiveDataset();
        $grant = ProjectGrant::create([
            'pid' => 'version-pid-' . uniqid(),
            'user_id' => $dataset->user_id,
            'team_id' => $dataset->team_id,
        ]);

        ProjectGrantVersionHasDataset::create([
            'project_grant_id' => $grant->id,
            'dataset_id' => $dataset->id,
        ]);

        $version = ProjectGrantVersion::create([
            'project_grant_id' => $grant->id,
            'version' => 1,
            'project_grant_name' => 'Version Observer Grant',
        ]);

        $this->observer->shouldReceive('reindexElastic')
            ->once()
            ->with((string) $dataset->id);

        $this->observer->shouldReceive('reindexElasticDataProviderWithRelations')
            ->once()
            ->with($dataset->team_id, 'dataset');

        $this->observer->created($version);
    }

    public function test_updated_reindexes_linked_active_datasets(): void
    {
        $dataset = $this->makeActiveDataset();
        $grant = ProjectGrant::create([
            'pid' => 'version-pid-' . uniqid(),
            'user_id' => $dataset->user_id,
            'team_id' => $dataset->team_id,
        ]);

        ProjectGrantVersionHasDataset::create([
            'project_grant_id' => $grant->id,
            'dataset_id' => $dataset->id,
        ]);

        $version = ProjectGrantVersion::create([
            'project_grant_id' => $grant->id,
            'version' => 1,
            'project_grant_name' => 'Version Observer Grant',
        ]);

        $this->observer->shouldReceive('reindexElastic')->once();
        $this->observer->shouldReceive('reindexElasticDataProviderWithRelations')->once();

        $version->project_grant_name = 'Updated Grant Name';
        $this->observer->updated($version);
    }
}
