<?php

namespace Tests\Unit\Modules;

use App\Models\Dataset;
use App\Modules\Contracts\EnrichmentStore;
use App\Modules\EnrichmentResult;
use App\Modules\Jobs\RunModuleEnrichment;
use App\Modules\ModuleManager;
use App\Observers\DatasetObserver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use Tests\Fakes\Modules\FakeModule;
use Tests\Fakes\Modules\InMemoryEnrichmentStore;
use Tests\TestCase;

class DatasetObserverModulesTest extends TestCase
{
    private FakeModule $module;

    private InMemoryEnrichmentStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        config(['modules.modules' => [
            'fake' => ['class' => FakeModule::class, 'partners' => ['PRUK'], 'subjects' => [Dataset::class]],
        ]]);
        $this->module = new FakeModule();
        $this->store = new InMemoryEnrichmentStore();
        $this->app->instance(FakeModule::class, $this->module);
        $this->app->instance(EnrichmentStore::class, $this->store);
        Feature::flushCache();
        Feature::activate(ModuleManager::FLAG);
        Http::preventStrayRequests();
    }

    private function prukDataset(): Dataset
    {
        // Not ACTIVE, so the observer's own active_date save doesn't run.
        return (new Dataset())->forceFill(['id' => 5, 'partner_context' => 'PRUK', 'status' => Dataset::STATUS_DRAFT]);
    }

    public function test_saving_a_partner_dataset_queues_a_pull_without_calling_the_source(): void
    {
        (new DatasetObserver())->updated($this->prukDataset());

        Queue::assertPushed(RunModuleEnrichment::class, fn ($job) => $job->module === 'fake'
            && $job->context->subjectId === 5
            && $job->context->origin === DatasetObserver::class);
        $this->assertSame(0, $this->module->enrichCalls);
        $this->assertSame(0, $this->module->versionCalls);
    }

    public function test_a_dataset_that_already_has_data_is_not_pulled_again_on_every_save(): void
    {
        $dataset = $this->prukDataset();
        $this->store->put(app(ModuleManager::class)->for($dataset)->context(), EnrichmentResult::ok([], '1')->forModule('fake'));

        (new DatasetObserver())->updated($dataset);

        Queue::assertNothingPushed();
    }

    public function test_nothing_is_queued_while_the_flag_is_off(): void
    {
        Feature::deactivate(ModuleManager::FLAG);

        (new DatasetObserver())->created($this->prukDataset());

        Queue::assertNothingPushed();
    }
}
