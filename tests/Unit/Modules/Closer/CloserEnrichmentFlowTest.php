<?php

namespace Tests\Unit\Modules\Closer;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\ModuleEnrichment;
use App\Models\Team;
use App\Modules\Closer\CloserClient;
use App\Modules\EnrichmentStatus;
use App\Modules\Jobs\RunModuleEnrichment;
use App\Modules\ModuleManager;
use App\Observers\DatasetVersionObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use Tests\TestCase;

/**
 * The real wiring end to end: saving a PRUK dataset whose metadata carries a
 * CLOSER URN queues a job, the job pulls from (faked) CLOSER, the summary is
 * stored, and a PRUK read sees it while a Gateway read does not.
 */
class CloserEnrichmentFlowTest extends TestCase
{
    private const ITEM = 'closer.example.test/api/v1/item/uk.example/11111111-2222-3333-4444-555555555555';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.closer.base_url' => 'https://closer.example.test',
            'services.closer.username' => 'example-user',
            'services.closer.password' => 'example-pass',
        ]);
        Cache::forget(CloserClient::TOKEN_CACHE_KEY);
        Feature::flushCache();
        Feature::activate(ModuleManager::FLAG);
        Http::preventStrayRequests();
        Http::fake([
            'closer.example.test/token/createtoken' => Http::response(['access_token' => 'tok', 'expires' => 3600]),
            self::ITEM.'/history' => Http::response([['Version' => 57]]),
            self::ITEM => Http::response([
                'Version' => 57,
                'VersionDate' => '2026-09-02T10:01:47.183Z',
                'Item' => file_get_contents(base_path('tests/Fixtures/closer/study_unit.xml')),
            ]),
        ]);
    }

    private function prukDatasetLinkedToCloser(): Dataset
    {
        $dataset = Dataset::factory()->create([
            'team_id' => Team::factory()->create()->id,
            'user_id' => 1,
            'status' => Dataset::STATUS_DRAFT,
            'partner_context' => 'PRUK',
        ]);
        DatasetVersion::factory()->create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'metadata' => ['metadata' => ['linkage' => ['url' => 'urn:ddi:uk.example:11111111-2222-3333-4444-555555555555:57']]],
        ]);

        return $dataset;
    }

    public function test_saving_a_linked_pruk_dataset_ends_with_a_stored_closer_summary(): void
    {
        $dataset = $this->prukDatasetLinkedToCloser();

        // The suite detaches model observers, so call the hook as a save would.
        (new DatasetVersionObserver())->created($dataset->versions()->sole());

        $job = Queue::pushed(RunModuleEnrichment::class)->first();
        $this->assertNotNull($job, 'the observer did not queue a pull');
        $job->handle(app(ModuleManager::class));

        $row = ModuleEnrichment::where('enrichable_id', $dataset->id)->sole();
        $this->assertSame('closer', $row->module);
        $this->assertSame(EnrichmentStatus::Ok, $row->status);
        $this->assertSame('uk.example/11111111-2222-3333-4444-555555555555:57', $row->source_version);
        $this->assertSame('PRUK', $row->partner);
        $this->assertSame('Example Cohort Pregnancy to 12 months', $row->payload['title']);

        request()->headers->set('x-partner-context', 'PRUK');
        $this->assertSame('Example Cohort Pregnancy to 12 months', $dataset->refresh()->modules()->resolve()->get('closer')?->payload['title'] ?? null);

        request()->headers->set('x-partner-context', 'HDRUK');
        app()->forgetScopedInstances();
        $this->assertTrue(app(ModuleManager::class)->for($dataset)->resolve()->isEmpty());
    }

    public function test_a_second_pull_with_an_unchanged_version_does_not_download_the_item_again(): void
    {
        $dataset = $this->prukDatasetLinkedToCloser();
        $manager = app(ModuleManager::class);
        $context = $manager->for($dataset)->context();

        $manager->run('closer', $context);
        $manager->run('closer', $context);

        Http::assertSentCount(4); // token, history, item, then history only
    }
}
