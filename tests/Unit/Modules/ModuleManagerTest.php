<?php

namespace Tests\Unit\Modules;

use App\Models\Dataset;
use App\Modules\Contracts\EnrichmentStore;
use App\Modules\EnrichmentResult;
use App\Modules\EnrichmentStatus;
use App\Modules\Jobs\RunModuleEnrichment;
use App\Modules\ModuleManager;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Laravel\Pennant\Feature;
use RuntimeException;
use Tests\Fakes\Modules\FakeModule;
use Tests\Fakes\Modules\InMemoryEnrichmentStore;
use Tests\TestCase;

class ModuleManagerTest extends TestCase
{
    private FakeModule $module;

    private InMemoryEnrichmentStore $store;

    private Dataset $dataset;

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
        $this->asPartner('PRUK');

        $this->dataset = (new Dataset())->forceFill(['id' => 42]);
    }

    private function asPartner(string $partner): void
    {
        request()->headers->set('x-partner-context', $partner);
    }

    private function manager(): ModuleManager
    {
        return app(ModuleManager::class);
    }

    private function storeResult(EnrichmentResult $result, array $params = []): void
    {
        $this->store->put($this->manager()->for($this->dataset, $params)->context(), $result);
    }

    // --- Gating: flag and partner ----------------------------------------

    public function test_nothing_runs_while_the_module_manager_flag_is_off(): void
    {
        Feature::deactivate(ModuleManager::FLAG);
        $scope = $this->manager()->for($this->dataset);

        $this->assertTrue($scope->resolve()->isEmpty());
        $this->assertSame(0, $scope->refresh());
        $this->assertSame(EnrichmentStatus::Skipped, $this->manager()->run('fake', $scope->context())->status);

        Queue::assertNothingPushed();
        $this->assertSame(0, $this->module->enrichCalls);
    }

    public function test_a_partner_the_module_is_not_enabled_for_gets_nothing(): void
    {
        $this->asPartner('HDRUK');
        $scope = $this->manager()->for($this->dataset);

        $this->assertTrue($scope->resolve()->isEmpty());
        $this->assertSame(0, $scope->refresh());
        Queue::assertNothingPushed();
    }

    public function test_a_module_with_no_partners_listed_is_off_for_everyone(): void
    {
        config(['modules.modules.fake.partners' => []]);

        $this->assertTrue($this->manager()->for($this->dataset)->resolve()->isEmpty());
    }

    public function test_a_subject_type_the_module_does_not_list_gets_nothing(): void
    {
        config(['modules.modules.fake.subjects' => [\App\Models\Tool::class]]);

        $this->assertTrue($this->manager()->for($this->dataset)->resolve()->isEmpty());
    }

    public function test_queued_work_keeps_the_partner_from_when_the_scope_was_created(): void
    {
        $scope = $this->manager()->for($this->dataset);
        $this->asPartner('HDRUK');

        $this->assertSame(1, $scope->refresh());

        Queue::assertPushed(RunModuleEnrichment::class, fn ($job) => $job->context->partner === 'PRUK');
    }

    // --- Gating: the subject's own partner_context -------------------------

    private function datasetFor(string $partner): Dataset
    {
        return (new Dataset())->forceFill(['id' => 43, 'partner_context' => $partner]);
    }

    public function test_the_gateway_cannot_read_enrichment_for_a_partner_dataset_it_can_view(): void
    {
        $this->asPartner('HDRUK');

        $this->assertTrue($this->manager()->for($this->datasetFor('PRUK'))->resolve()->isEmpty());
    }

    public function test_a_partner_dataset_is_still_pulled_when_saved_from_a_gateway_request(): void
    {
        $this->asPartner('HDRUK');

        $this->assertSame(1, $this->manager()->for($this->datasetFor('PRUK'))->refresh());
    }

    public function test_a_partner_dataset_is_pulled_when_there_is_no_partner_header_at_all(): void
    {
        request()->headers->remove('x-partner-context');
        $context = $this->manager()->for($this->datasetFor('PRUK'))->context();

        $result = $this->manager()->run('fake', $context);

        $this->assertSame(EnrichmentStatus::Ok, $result->status);
        $this->assertSame('PRUK', $this->module->contexts[0]->owningPartner());
    }

    public function test_a_gateway_dataset_gets_nothing_even_through_an_enabled_partner(): void
    {
        $scope = $this->manager()->for($this->datasetFor('HDRUK'));

        $this->assertTrue($scope->resolve()->isEmpty());
        $this->assertSame(0, $scope->refresh());
    }

    public function test_the_owning_partner_reads_its_own_enrichment(): void
    {
        $this->assertSame(['fake'], $this->manager()->for($this->datasetFor('PRUK'))->resolve()->keys()->all());
    }

    public function test_an_unsupported_subject_is_skipped_with_a_reason_and_never_enriched(): void
    {
        $this->module->supports = false;
        $scope = $this->manager()->for($this->dataset);

        $result = $scope->resolve()->get('fake');
        $this->assertSame(EnrichmentStatus::Skipped, $result->status);
        $this->assertNotEmpty($result->reason);

        $this->assertSame(EnrichmentStatus::Skipped, $this->manager()->run('fake', $scope->context())->status);
        $this->assertSame(0, $this->module->enrichCalls);
        $this->assertSame(0, $scope->refresh());
    }

    // --- Failure handling --------------------------------------------------

    public function test_a_failing_module_returns_a_failed_result_instead_of_throwing(): void
    {
        $this->module->throws = new ConnectionException('timed out');

        $result = $this->manager()->run('fake', $this->manager()->for($this->dataset)->context());

        $this->assertSame(EnrichmentStatus::Failed, $result->status);
        $this->assertSame([], $result->payload);
        $this->assertSame(EnrichmentStatus::Failed, $this->manager()->for($this->dataset)->resolve()->get('fake')->status);
    }

    public function test_a_failure_keeps_the_last_good_payload_and_marks_it_stale(): void
    {
        $this->storeResult(EnrichmentResult::ok(['kept' => true], '1')->forModule('fake'));
        $this->module->version = '2';
        $this->module->throws = new RuntimeException('boom');

        $result = $this->manager()->run('fake', $this->manager()->for($this->dataset)->context());

        $this->assertSame(EnrichmentStatus::Stale, $result->status);
        $this->assertSame(['kept' => true], $result->payload);
        $this->assertSame('1', $result->sourceVersion);
        $this->assertStringContainsString('boom', $result->reason);
    }

    public function test_a_failing_version_check_is_also_caught(): void
    {
        $this->module->version = null;
        $failing = new class () extends FakeModule {
            public function sourceVersion(\App\Modules\EnrichmentContext $context): ?string
            {
                throw new ConnectionException('timed out');
            }
        };
        $this->app->instance(FakeModule::class, $failing);

        $result = $this->manager()->run('fake', $this->manager()->for($this->dataset)->context());

        $this->assertSame(EnrichmentStatus::Failed, $result->status);
    }

    // --- Reading versus refreshing -----------------------------------------

    public function test_resolve_only_reads_what_is_stored_and_never_calls_the_module(): void
    {
        $this->assertSame(EnrichmentStatus::Missing, $this->manager()->for($this->dataset)->resolve()->get('fake')->status);

        $this->storeResult(EnrichmentResult::ok(['stored' => true], '1')->forModule('fake'));
        $result = $this->manager()->for($this->dataset)->resolve()->get('fake');

        $this->assertSame(['stored' => true], $result->payload);
        $this->assertSame(0, $this->module->enrichCalls);
        $this->assertSame(0, $this->module->versionCalls);
    }

    public function test_results_are_kept_apart_by_params(): void
    {
        $this->storeResult(EnrichmentResult::ok(['wave' => 1], '1')->forModule('fake'), ['wave' => 1]);

        $this->assertSame(['wave' => 1], $this->manager()->for($this->dataset, ['wave' => 1])->resolve()->get('fake')->payload);
        $this->assertSame(EnrichmentStatus::Missing, $this->manager()->for($this->dataset, ['wave' => 2])->resolve()->get('fake')->status);
    }

    public function test_refresh_queues_a_job_rather_than_running_the_module_inline(): void
    {
        $this->assertSame(1, $this->manager()->for($this->dataset, ['wave' => 3])->refresh());

        Queue::assertPushed(RunModuleEnrichment::class, fn ($job) => $job->module === 'fake'
            && $job->context->params === ['wave' => 3]);
        $this->assertSame(0, $this->module->enrichCalls);
        $this->assertSame(0, $this->module->versionCalls);
    }

    public function test_an_unchanged_source_version_is_not_downloaded_again(): void
    {
        $this->storeResult(EnrichmentResult::ok(['old' => true], '1')->forModule('fake'));

        $result = $this->manager()->run('fake', $this->manager()->for($this->dataset)->context());

        $this->assertSame(0, $this->module->enrichCalls);
        $this->assertSame(['old' => true], $result->payload);
    }

    public function test_a_changed_source_version_or_force_downloads_again(): void
    {
        $context = $this->manager()->for($this->dataset)->context();
        $this->storeResult(EnrichmentResult::ok(['old' => true], '1')->forModule('fake'));

        $this->manager()->run('fake', $context, force: true);
        $this->assertSame(1, $this->module->enrichCalls);

        $this->module->version = '2';
        $result = $this->manager()->run('fake', $context);
        $this->assertSame(2, $this->module->enrichCalls);
        $this->assertSame('2', $this->store->find('fake', $context)->sourceVersion);
        $this->assertSame(EnrichmentStatus::Ok, $result->status);
    }

    public function test_an_unknown_source_version_always_downloads(): void
    {
        $this->module->version = null;
        $this->storeResult(EnrichmentResult::ok([], null)->forModule('fake'));

        $this->manager()->run('fake', $this->manager()->for($this->dataset)->context());

        $this->assertSame(1, $this->module->enrichCalls);
    }

    public function test_the_queued_job_runs_the_module_with_the_captured_context(): void
    {
        $this->manager()->for($this->dataset, ['wave' => 3])->refresh();
        $this->asPartner('HDRUK');

        Queue::pushed(RunModuleEnrichment::class)->first()->handle(app(ModuleManager::class));

        $this->assertSame(1, $this->module->enrichCalls);
        $this->assertSame('PRUK', $this->module->contexts[0]->partner);
        $this->assertSame(['wave' => 3], $this->module->contexts[0]->params);
    }

    public function test_one_job_per_module_subject_and_params(): void
    {
        $context = fn (array $params) => $this->manager()->for($this->dataset, $params)->context();

        $this->assertSame(
            (new RunModuleEnrichment('fake', $context(['a' => 1, 'b' => 2])))->uniqueId(),
            (new RunModuleEnrichment('fake', $context(['b' => 2, 'a' => 1])))->uniqueId(),
        );
        $this->assertNotSame(
            (new RunModuleEnrichment('fake', $context(['wave' => 1])))->uniqueId(),
            (new RunModuleEnrichment('fake', $context(['wave' => 2])))->uniqueId(),
        );
    }

    public function test_refresh_missing_queues_only_modules_with_nothing_usable_stored(): void
    {
        config(['modules.modules.other' => ['class' => FakeModule::class, 'partners' => ['PRUK'], 'subjects' => [Dataset::class]]]);
        $this->storeResult(EnrichmentResult::ok(['have' => true], '1')->forModule('fake'));

        $this->assertSame(1, $this->manager()->for($this->dataset)->refreshMissing());

        Queue::assertPushed(RunModuleEnrichment::class, 1);
        Queue::assertPushed(RunModuleEnrichment::class, fn ($job) => $job->module === 'other');
    }

    public function test_refresh_missing_retries_a_failed_pull(): void
    {
        $this->storeResult(EnrichmentResult::failed('earlier failure')->forModule('fake'));

        $this->assertSame(1, $this->manager()->for($this->dataset)->refreshMissing());
    }

    public function test_queueing_never_throws_even_when_the_store_does(): void
    {
        $this->app->instance(\App\Modules\Contracts\EnrichmentStore::class, new class () extends InMemoryEnrichmentStore {
            public function find(string $module, \App\Modules\EnrichmentContext $context): ?EnrichmentResult
            {
                throw new RuntimeException('database down');
            }
        });
        $this->app->forgetScopedInstances();

        $this->assertSame(0, $this->manager()->for($this->dataset)->refreshMissing());
    }

    // --- Scope ---------------------------------------------------------------

    public function test_naming_a_module_that_does_not_exist_is_an_error(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->manager()->for($this->dataset)->only('closr');
    }

    public function test_only_limits_the_scope_to_the_named_modules(): void
    {
        config(['modules.modules.other' => ['class' => FakeModule::class, 'partners' => ['PRUK'], 'subjects' => [Dataset::class]]]);

        $this->assertSame(['fake', 'other'], $this->manager()->for($this->dataset)->resolve()->keys()->all());
        $this->assertSame(['other'], $this->manager()->for($this->dataset)->only('other')->resolve()->keys()->all());
    }

    public function test_each_request_gets_its_own_manager(): void
    {
        $first = $this->manager();
        $this->app->forgetScopedInstances();

        $this->assertNotSame($first, $this->manager());
    }
}
