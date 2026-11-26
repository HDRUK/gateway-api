<?php

namespace Tests\Unit\Modules;

use App\Models\Dataset;
use App\Models\ModuleEnrichment;
use App\Modules\Contracts\EnrichmentStore;
use App\Modules\EnrichmentContext;
use App\Modules\EnrichmentResult;
use App\Modules\EnrichmentStatus;
use App\Modules\Stores\EloquentEnrichmentStore;
use Tests\TestCase;

class EloquentEnrichmentStoreTest extends TestCase
{
    private EloquentEnrichmentStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new EloquentEnrichmentStore();
    }

    private function context(array $params = [], int $id = 42): EnrichmentContext
    {
        return new EnrichmentContext(Dataset::class, $id, 'PRUK', $params, subjectPartner: 'PRUK');
    }

    public function test_it_is_the_store_the_app_uses(): void
    {
        $this->assertInstanceOf(EloquentEnrichmentStore::class, app(EnrichmentStore::class));
    }

    public function test_nothing_stored_reads_as_null(): void
    {
        $this->assertNull($this->store->find('closer', $this->context()));
    }

    public function test_a_result_round_trips_with_everything_it_carries(): void
    {
        $stored = EnrichmentResult::ok(['title' => 'Wave 1', 'counts' => ['Variable' => 3]], '57')->forModule('closer');

        $this->store->put($this->context(['wave' => 1]), $stored);
        $found = $this->store->find('closer', $this->context(['wave' => 1]));

        $this->assertSame('closer', $found->module);
        $this->assertSame(EnrichmentStatus::Ok, $found->status);
        $this->assertSame(['title' => 'Wave 1', 'counts' => ['Variable' => 3]], $found->payload);
        $this->assertSame('57', $found->sourceVersion);
        $this->assertSame($stored->fetchedAt->toIso8601String(), $found->fetchedAt->toIso8601String());
    }

    public function test_a_later_result_replaces_the_earlier_one_rather_than_adding_a_row(): void
    {
        $this->store->put($this->context(), EnrichmentResult::ok(['v' => 1], '1')->forModule('closer'));
        $this->store->put($this->context(), EnrichmentResult::ok(['v' => 1], '1')->forModule('closer')->stale('boom'));

        $this->assertSame(1, ModuleEnrichment::count());
        $found = $this->store->find('closer', $this->context());
        $this->assertSame(EnrichmentStatus::Stale, $found->status);
        $this->assertSame('boom', $found->reason);
        $this->assertSame(['v' => 1], $found->payload);
    }

    public function test_results_are_kept_apart_by_module_subject_and_params(): void
    {
        $this->store->put($this->context(['wave' => 1]), EnrichmentResult::ok(['n' => 1])->forModule('closer'));
        $this->store->put($this->context(['wave' => 2]), EnrichmentResult::ok(['n' => 2])->forModule('closer'));
        $this->store->put($this->context(['wave' => 1], 99), EnrichmentResult::ok(['n' => 3])->forModule('closer'));
        $this->store->put($this->context(['wave' => 1]), EnrichmentResult::ok(['n' => 4])->forModule('other'));

        $this->assertSame(4, ModuleEnrichment::count());
        $this->assertSame(['n' => 1], $this->store->find('closer', $this->context(['wave' => 1]))->payload);
        $this->assertSame(['n' => 2], $this->store->find('closer', $this->context(['wave' => 2]))->payload);
        $this->assertSame(['n' => 3], $this->store->find('closer', $this->context(['wave' => 1], 99))->payload);
        $this->assertSame(['n' => 4], $this->store->find('other', $this->context(['wave' => 1]))->payload);
    }

    public function test_skipped_and_missing_results_are_never_written(): void
    {
        $this->store->put($this->context(), EnrichmentResult::skipped('not applicable')->forModule('closer'));
        $this->store->put($this->context(), EnrichmentResult::missing()->forModule('closer'));

        $this->assertSame(0, ModuleEnrichment::count());
    }
}
