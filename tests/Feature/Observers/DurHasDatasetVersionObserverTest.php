<?php

namespace Tests\Feature\Observers;

use Mockery;
use App\Models\Dur;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\DatasetVersion;
use Database\Seeders\DurSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\ToolSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\KeywordSeeder;
use Database\Seeders\LicenseSeeder;
use App\Models\DurHasDatasetVersion;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\ApplicationSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\TypeCategorySeeder;
use Database\Seeders\DatasetVersionSeeder;
use App\Models\CollectionHasDatasetVersion;
use Database\Seeders\SpatialCoverageSeeder;
use Database\Seeders\CollectionHasUserSeeder;
use Database\Seeders\ProgrammingPackageSeeder;
use App\Observers\DurHasDatasetVersionObserver;
use Database\Seeders\ProgrammingLanguageSeeder;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class DurHasDatasetVersionObserverTest extends TestCase
{
    use FastRefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $userId;
    protected $teamId;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Collection::flushEventListeners();
        CollectionHasDatasetVersion::flushEventListeners();
        Dur::flushEventListeners();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
            CategorySeeder::class,
            TypeCategorySeeder::class,
            ProgrammingLanguageSeeder::class,
            ProgrammingPackageSeeder::class,
            LicenseSeeder::class,
            TagSeeder::class,
            ApplicationSeeder::class,
            CollectionSeeder::class,
            CollectionHasUserSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            KeywordSeeder::class,
            ToolSeeder::class,
            DurSeeder::class,

        ]);
    }

    public function testDurHasDatasetVersionObserverCallsElasticMethodOnCreatedEvent()
    {
        $observer = Mockery::mock(DurHasDatasetVersionObserver::class)->makePartial();
        app()->instance(DurHasDatasetVersionObserver::class, $observer);

        $observer
            ->shouldReceive('elasticDurHasDatasetVersion')
            ->once()
            ->andReturnTrue();

        $dataset = Dataset::where('status', Dataset::STATUS_ACTIVE)->first();
        $latestMetadata = $dataset->latestMetadata()->first();
        $datasetVersionId = $latestMetadata->id;

        $dur = Dur::where('status', Dur::STATUS_ACTIVE)->select('id')->first();

        DurHasDatasetVersion::create([
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
            'user_id' => $dur->user_id,
        ]);

        $this->assertDatabaseHas('dur_has_dataset_version', [
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
            'user_id' => $dur->user_id,
        ]);

        $this->assertTrue(true);
    }

    public function testDurHasDatasetVersionObserverElasticMethodOnUpdatedEvent()
    {
        $observer = Mockery::mock(DurHasDatasetVersionObserver::class)->makePartial();
        app()->instance(DurHasDatasetVersionObserver::class, $observer);

        $observer
            ->shouldReceive('elasticDurHasDatasetVersion')
            ->twice()
            ->andReturnTrue();

        $dataset = Dataset::where('status', Dataset::STATUS_ACTIVE)->first();
        $latestMetadata = $dataset->latestMetadata()->first();
        $datasetVersionId = $latestMetadata->id;

        $dur = Dur::where('status', Dur::STATUS_ACTIVE)->select('id')->first();

        $durHasDatasetVersion = DurHasDatasetVersion::create([
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
            'user_id' => $dur->user_id,
        ]);

        $durHasDatasetVersion->update(['updated_at' => now()]);

        $this->assertDatabaseHas('dur_has_dataset_version', [
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
            'user_id' => $dur->user_id,
        ]);

        $this->assertTrue(true);
    }

    public function testDurHasDatasetVersionObserverCallsElasticMethodOnDeletedEvent()
    {
        $observer = Mockery::mock(DurHasDatasetVersionObserver::class)->makePartial();
        app()->instance(DurHasDatasetVersionObserver::class, $observer);

        $observer
            ->shouldReceive('elasticDurHasDatasetVersion')
            ->once()
            ->andReturnTrue();

        $dataset = Dataset::where('status', Dataset::STATUS_ACTIVE)->first();
        $latestMetadata = $dataset->latestMetadata()->first();
        $datasetVersionId = $latestMetadata->id;

        $dur = Dur::where('status', Dur::STATUS_ACTIVE)->select('id')->first();

        DurHasDatasetVersion::create([
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
            'user_id' => $dur->user_id,
        ]);

        DurHasDatasetVersion::where([
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
            'user_id' => $dur->user_id,
        ])->delete();

        // Assertions
        $this->assertSoftDeleted('dur_has_dataset_version', [
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
        ]);
        $this->assertTrue(true);
    }
}
