<?php

namespace Tests\Unit\Modules;

use App\Models\Dataset;
use App\Modules\Concerns\HasModules;
use App\Modules\Contracts\EnrichmentStore;
use App\Modules\ModuleManager;
use App\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Context;
use InvalidArgumentException;
use Laravel\Pennant\Feature;
use LogicException;
use RuntimeException;
use Tests\Fakes\Modules\FakeModule;
use Tests\Fakes\Modules\InMemoryEnrichmentStore;
use Tests\TestCase;

class HasModulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['modules.modules' => [
            'fake' => ['class' => FakeModule::class, 'partners' => ['PRUK'], 'subjects' => [Dataset::class]],
        ]]);
        $this->app->instance(FakeModule::class, new FakeModule());
        $this->app->instance(EnrichmentStore::class, new InMemoryEnrichmentStore());
        Feature::flushCache();
        Feature::activate(ModuleManager::FLAG);
        request()->headers->set('x-partner-context', 'PRUK');
    }

    private function dataset(): Dataset
    {
        return (new Dataset())->forceFill(['id' => 7]);
    }

    public function test_a_model_using_the_trait_is_its_own_subject(): void
    {
        $model = new class () extends Dataset {
            use HasModules;
        };
        $model->forceFill(['id' => 9]);

        $context = $model->modules(params: ['wave' => 1])->context();

        $this->assertSame(9, $context->subjectId);
        $this->assertSame(['wave' => 1], $context->params);
        $this->assertSame($model::class, $context->origin);
    }

    public function test_a_controller_or_observer_passes_the_subject_and_is_recorded_as_the_origin(): void
    {
        $caller = new class () {
            use HasModules;

            public function scope(Dataset $dataset)
            {
                return $this->modules($dataset, ['wave' => 2]);
            }
        };

        $context = $caller->scope($this->dataset())->context();

        $this->assertSame(Dataset::class, $context->subjectType);
        $this->assertSame(7, $context->subjectId);
        $this->assertSame('PRUK', $context->partner);
        $this->assertSame($caller::class, $context->origin);
    }

    public function test_a_non_model_without_a_subject_is_an_error(): void
    {
        $caller = new class () {
            use HasModules;
        };

        $this->expectException(LogicException::class);

        $caller->modules();
    }

    public function test_within_adds_log_context_for_the_callback_and_removes_it_afterwards(): void
    {
        $scope = app(ModuleManager::class)->for($this->dataset());

        $inside = $scope->within(fn () => Context::get('module_subject'));

        $this->assertSame(Dataset::class.':7', $inside);
        $this->assertNull(Context::get('module_subject'));
    }

    public function test_within_removes_log_context_even_when_the_callback_throws(): void
    {
        $scope = app(ModuleManager::class)->for($this->dataset());

        try {
            $scope->within(fn () => throw new RuntimeException('caller failed'));
        } catch (RuntimeException) {
        }

        $this->assertNull(Context::get('module_subject'));
    }

    public function test_a_module_class_that_does_not_implement_the_contract_is_rejected(): void
    {
        config(['modules.modules.broken' => ['class' => \stdClass::class, 'partners' => ['PRUK'], 'subjects' => [Dataset::class]]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('broken');

        new ModuleRegistry(config('modules.modules'));
    }
}
