<?php

namespace Tests\Unit\Modules\Closer;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Modules\Closer\CloserClient;
use App\Modules\Closer\CloserModule;
use App\Modules\EnrichmentContext;
use App\Modules\EnrichmentStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class CloserModuleTest extends TestCase
{
    private const URN = 'urn:ddi:uk.example:11111111-2222-3333-4444-555555555555';

    private const ITEM = 'closer.example.test/api/v1/item/uk.example/11111111-2222-3333-4444-555555555555';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.closer.base_url' => 'https://closer.example.test',
            'services.closer.username' => 'example-user',
            'services.closer.password' => 'example-pass',
            'services.closer.dataset_identifiers' => [],
        ]);
        Cache::forget(CloserClient::TOKEN_CACHE_KEY);
        Http::preventStrayRequests();
    }

    private function module(): CloserModule
    {
        return app(CloserModule::class);
    }

    private function context(int $datasetId = 1, array $params = []): EnrichmentContext
    {
        return new EnrichmentContext(Dataset::class, $datasetId, 'PRUK', $params, subjectPartner: 'PRUK');
    }

    private function fakeCloser(array $routes): void
    {
        Http::fake(['closer.example.test/token/createtoken' => Http::response(['access_token' => 'tok', 'expires' => 3600])] + $routes);
    }

    private function datasetWithMetadata(array $metadata): int
    {
        $dataset = Dataset::factory()->create([
            'team_id' => Team::factory()->create()->id,
            'user_id' => 1,
            'status' => Dataset::STATUS_ACTIVE,
            'partner_context' => 'PRUK',
        ]);
        DatasetVersion::factory()->create(['dataset_id' => $dataset->id, 'version' => 1, 'metadata' => $metadata]);

        return $dataset->id;
    }

    // --- Finding the CLOSER identifier ---------------------------------------

    public function test_a_dataset_with_no_closer_identifier_anywhere_is_not_supported(): void
    {
        $id = $this->datasetWithMetadata(['metadata' => ['summary' => ['title' => 'No link here']]]);

        $this->assertFalse($this->module()->supports($this->context($id)));
        Http::assertNothingSent();
    }

    public function test_a_ddi_urn_anywhere_in_the_latest_metadata_is_found(): void
    {
        $id = $this->datasetWithMetadata(['metadata' => ['linkage' => ['url' => self::URN.':12']]]);

        $this->assertTrue($this->module()->supports($this->context($id)));
        $this->assertSame(
            ['agency' => 'uk.example', 'id' => '11111111-2222-3333-4444-555555555555', 'version' => 12],
            $this->module()->identifierFor($this->context($id)),
        );
    }

    public function test_the_sample_pruk_wave_record_is_linked_through_its_wave_id(): void
    {
        $envelope = json_decode(file_get_contents(base_path('tests/Unit/test_files/pruk_dummy_data/dataset_11.json')), true);
        $id = $this->datasetWithMetadata($envelope);

        $this->assertSame(
            ['agency' => 'uk.alspac', 'id' => 'e9ae5d70-a3db-470f-9e9e-e1ed4636f1e6', 'version' => 57],
            $this->module()->identifierFor($this->context($id)),
        );
    }

    public function test_the_config_override_wins_over_metadata(): void
    {
        $id = $this->datasetWithMetadata(['metadata' => ['linkage' => ['url' => 'urn:ddi:uk.other:aaaa-bbbb:1']]]);
        config(['services.closer.dataset_identifiers' => [$id => self::URN]]);

        $this->assertSame('uk.example', $this->module()->identifierFor($this->context($id))['agency']);
    }

    public function test_an_identifier_passed_in_params_wins_over_everything(): void
    {
        config(['services.closer.dataset_identifiers' => [1 => 'urn:ddi:uk.other:aaaa-bbbb:1']]);

        $identifier = $this->module()->identifierFor($this->context(1, ['identifier' => self::URN]));

        $this->assertSame('uk.example', $identifier['agency']);
    }

    // --- Version check -------------------------------------------------------

    public function test_the_source_version_is_the_latest_in_the_item_history(): void
    {
        $this->fakeCloser([self::ITEM.'/history' => Http::response([['Version' => 55], ['Version' => 57], ['Version' => 56]])]);

        $this->assertSame(
            'uk.example/11111111-2222-3333-4444-555555555555:57',
            $this->module()->sourceVersion($this->context(1, ['identifier' => self::URN])),
        );
    }

    public function test_pointing_a_dataset_at_a_different_wave_changes_the_source_version(): void
    {
        $other = 'closer.example.test/api/v1/item/uk.example/99999999-2222-3333-4444-555555555555';
        $this->fakeCloser([
            self::ITEM.'/history' => Http::response([['Version' => 57]]),
            $other.'/history' => Http::response([['Version' => 57]]),
        ]);

        $this->assertNotSame(
            $this->module()->sourceVersion($this->context(1, ['identifier' => self::URN])),
            $this->module()->sourceVersion($this->context(1, ['identifier' => 'urn:ddi:uk.example:99999999-2222-3333-4444-555555555555'])),
        );
    }

    // --- Enrichment ------------------------------------------------------------

    public function test_it_summarises_the_study_unit_without_any_personal_data(): void
    {
        $this->fakeCloser([self::ITEM => Http::response([
            'ItemType' => '30ea0200-7121-4f01-8d21-a931a182b86d',
            'Version' => 57,
            'VersionDate' => '2026-09-02T10:01:47.183Z',
            'VersionResponsibility' => 'user@example.com',
            'Item' => file_get_contents(base_path('tests/Fixtures/closer/study_unit.xml')),
        ])]);

        $result = $this->module()->enrich($this->context(1, ['identifier' => self::URN]));

        $this->assertSame(EnrichmentStatus::Ok, $result->status);
        $this->assertSame('uk.example/11111111-2222-3333-4444-555555555555:57', $result->sourceVersion);
        $this->assertSame([
            'identifier' => self::URN.':57',
            'url' => 'https://discovery.closer.ac.uk/item/uk.example/11111111-2222-3333-4444-555555555555',
            'type' => 'StudyUnit',
            'title' => 'Example Cohort Pregnancy to 12 months',
            'abstract' => "Questionnaire data about pregnancy.\n\nStudy website: https://example.org/",
            'life_stage' => 'Pregnancy, birth and infancy',
            'life_stage_description' => 'Pregnancy - 1 year',
            'start_date' => '1990-09',
            'end_date' => '1993-10',
            'kind_of_data' => ['Survey Data', 'Measurement Data'],
            'analysis_unit' => 'Individual',
            'publisher' => 'Example University',
            'counts' => ['datasets' => 3, 'data_collections' => 2],
            'version_date' => '2026-09-02T10:01:47.183Z',
        ], $result->payload);
        $this->assertStringNotContainsString('example.com', json_encode($result->payload));
    }

    public function test_a_successful_pull_queues_a_harvest_of_the_whole_wave(): void
    {
        $this->fakeCloser([self::ITEM => Http::response([
            'Version' => 57,
            'Item' => file_get_contents(base_path('tests/Fixtures/closer/study_unit.xml')),
        ])]);

        $this->module()->enrich($this->context(1, ['identifier' => self::URN]));

        \Illuminate\Support\Facades\Queue::assertPushed(
            \App\Modules\Closer\Jobs\HarvestCloserWave::class,
            fn ($job) => $job->agency === 'uk.example' && $job->identifier === '11111111-2222-3333-4444-555555555555'
                && $job->queue === 'enrichment',
        );
    }

    public function test_an_identifier_closer_does_not_know_is_empty_not_failed(): void
    {
        $this->fakeCloser([self::ITEM => Http::response('', 404)]);

        $result = $this->module()->enrich($this->context(1, ['identifier' => self::URN]));

        $this->assertSame(EnrichmentStatus::Empty, $result->status);
        \Illuminate\Support\Facades\Queue::assertNothingPushed();
    }

    public function test_any_other_error_from_closer_is_thrown_for_the_manager_to_record(): void
    {
        $this->fakeCloser([self::ITEM => Http::response('', 500)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        $this->module()->enrich($this->context(1, ['identifier' => self::URN]));
    }

    public function test_malformed_xml_is_an_error_rather_than_an_empty_summary(): void
    {
        $this->fakeCloser([self::ITEM => Http::response(['Version' => 1, 'Item' => '<Fragment><unclosed>'])]);

        $this->expectException(RuntimeException::class);

        $this->module()->enrich($this->context(1, ['identifier' => self::URN]));
    }
}
