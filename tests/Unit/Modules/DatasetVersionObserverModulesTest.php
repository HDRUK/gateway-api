<?php

namespace Tests\Unit\Modules;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Modules\Contracts\EnrichmentStore;
use App\Modules\Jobs\RunModuleEnrichment;
use App\Modules\ModuleManager;
use App\Observers\DatasetObserver;
use App\Observers\DatasetVersionObserver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use Tests\Fakes\Modules\FakeModule;
use Tests\Fakes\Modules\InMemoryEnrichmentStore;
use Tests\TestCase;

/**
 * Enrichment is triggered by the dataset's *version*, because DatasetService
 * saves the Dataset row before the version that carries the new metadata.
 * The suite detaches model observers, so these call them directly, as a save would.
 */
class DatasetVersionObserverModulesTest extends TestCase
{
    private FakeModule $module;

    protected function setUp(): void
    {
        parent::setUp();

        config(['modules.modules' => [
            'fake' => ['class' => FakeModule::class, 'partners' => ['PRUK'], 'subjects' => [Dataset::class]],
        ]]);
        $this->module = new FakeModule();
        $this->app->instance(FakeModule::class, $this->module);
        $this->app->instance(EnrichmentStore::class, new InMemoryEnrichmentStore());
        Feature::flushCache();
        Feature::activate(ModuleManager::FLAG);
        Http::preventStrayRequests();
    }

    private function version(string $partner = 'PRUK'): DatasetVersion
    {
        $dataset = Dataset::factory()->create([
            'team_id' => Team::factory()->create()->id,
            'user_id' => 1,
            'status' => Dataset::STATUS_DRAFT,
            'partner_context' => $partner,
        ]);

        return DatasetVersion::factory()->create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'metadata' => ['metadata' => ['summary' => ['title' => 'Example']]],
        ]);
    }

    public function test_a_new_version_queues_a_pull_for_its_dataset_without_calling_the_source(): void
    {
        $version = $this->version();

        (new DatasetVersionObserver())->created($version);

        Queue::assertPushed(RunModuleEnrichment::class, fn ($job) => $job->module === 'fake'
            && $job->context->subjectType === Dataset::class
            && $job->context->subjectId === $version->dataset_id
            && $job->context->owningPartner() === 'PRUK'
            && $job->context->origin === DatasetVersionObserver::class);
        $this->assertSame(0, $this->module->enrichCalls);
        $this->assertSame(0, $this->module->versionCalls);
    }

    public function test_changed_metadata_on_an_existing_version_queues_a_pull(): void
    {
        $version = $this->version();
        $version->metadata = ['metadata' => ['summary' => ['title' => 'Edited in place']]];
        $version->saveQuietly();

        (new DatasetVersionObserver())->updated($version);

        Queue::assertPushed(RunModuleEnrichment::class, 1);
    }

    public function test_a_version_update_that_leaves_metadata_alone_queues_nothing(): void
    {
        $version = $this->version();
        $version->active_date = now();
        $version->saveQuietly();
        Queue::fake(); // ignore what building the version queued

        (new DatasetVersionObserver())->updated($version);

        Queue::assertNotPushed(RunModuleEnrichment::class);
    }

    public function test_a_gateway_dataset_version_queues_nothing(): void
    {
        (new DatasetVersionObserver())->created($this->version('HDRUK'));

        Queue::assertNotPushed(RunModuleEnrichment::class);
    }

    public function test_nothing_is_queued_while_the_flag_is_off(): void
    {
        Feature::deactivate(ModuleManager::FLAG);

        (new DatasetVersionObserver())->created($this->version());

        Queue::assertNotPushed(RunModuleEnrichment::class);
    }

    public function test_saving_the_dataset_row_alone_no_longer_queues_a_pull(): void
    {
        $dataset = Dataset::findOrFail($this->version()->dataset_id);
        Queue::fake(); // ignore what building the version queued

        // Its metadata is the previous version's at that point in a save.
        (new DatasetObserver())->updated($dataset);

        Queue::assertNotPushed(RunModuleEnrichment::class);
    }

    public function test_the_pull_is_only_queued_once_the_save_has_committed(): void
    {
        $dataset = Dataset::findOrFail($this->version()->dataset_id);
        $job = new RunModuleEnrichment('fake', app(ModuleManager::class)->for($dataset)->context());

        $this->assertTrue($job->afterCommit);
    }
}
