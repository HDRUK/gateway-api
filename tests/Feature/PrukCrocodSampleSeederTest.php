<?php

namespace Tests\Feature;

use App\Jobs\IndexDataset;
use App\Jobs\LinkageExtraction;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\Gwdm\GwdmHandlerFactory;
use Database\Seeders\PrukCrocodSampleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Coverage for the GWDM 2.3 sample records: a PRUK dataset and a CROCOD case
 * study. The case study must be stored like any dataset but kept out of both
 * search indexes; the PRUK dataset's waveOf must become a linkage row.
 */
class PrukCrocodSampleSeederTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private const PRUK_PID = 'b00255bb-4d78-468d-a436-e08f4af23daa';
    private const CROCOD_PID = '3f1c9a2e-7b4d-4e8a-9c21-5d6e7f8a9b0c';

    protected function setUp(): void
    {
        $this->commonSetUp();
        $this->seed(PrukCrocodSampleSeeder::class);
    }

    private function latestVersion(string $pid): DatasetVersion
    {
        return Dataset::where('pid', $pid)->firstOrFail()->latestVersion();
    }

    public function test_seeds_both_samples_as_gwdm_2_4_with_their_source_schema(): void
    {
        $pruk = Dataset::where('pid', self::PRUK_PID)->firstOrFail();
        $crocod = Dataset::where('pid', self::CROCOD_PID)->firstOrFail();

        $this->assertSame('PRUK', $pruk->partner_context);
        $this->assertSame('CROCOD', $crocod->partner_context);
        $this->assertSame(Dataset::STATUS_ACTIVE, $crocod->status);

        $prukVersion = $pruk->latestVersion();
        $this->assertSame('2.3', $prukVersion->gwdm_version);
        $this->assertSame('PRUK', $prukVersion->source_schema_model);
        $this->assertSame('1.0.0', $prukVersion->source_schema_version);
        $this->assertSame('Generation Scotland — 2015 Genotyping', $prukVersion->title);

        $crocodVersion = $crocod->latestVersion();
        $this->assertSame('CROCOD', $crocodVersion->source_schema_model);
        $this->assertSame('LENS', $crocodVersion->short_title);
    }

    public function test_reseeding_does_not_duplicate_the_samples(): void
    {
        $this->seed(PrukCrocodSampleSeeder::class);

        $this->assertSame(1, Dataset::where('pid', self::PRUK_PID)->count());
        $this->assertSame(1, DatasetVersion::where('dataset_id', Dataset::where('pid', self::CROCOD_PID)->value('id'))->count());
    }

    public function test_pruk_wave_of_is_extracted_as_a_linkage(): void
    {
        $version = $this->latestVersion(self::PRUK_PID);

        // MockExternalApis fakes LinkageExtraction, so check the seeder queued it,
        // then run it directly.
        Queue::assertPushed(
            LinkageExtraction::class,
            fn (LinkageExtraction $job) => in_array('version:' . $version->id, $job->tags(), true)
        );
        (new LinkageExtraction((string) $version->dataset_id, (string) $version->id))
            ->handle(app(GwdmHandlerFactory::class));

        $linkage = DB::table('dataset_version_has_dataset_version')
            ->where('dataset_version_source_id', $version->id)
            ->where('linkage_type', 'waveOf')
            ->first();

        $this->assertNotNull($linkage);
        $this->assertSame('Generation Scotland (Scottish Family Health Study)', $linkage->raw_title);
    }

    public function test_case_study_is_kept_out_of_typesense_but_the_pruk_dataset_is_not(): void
    {
        $this->assertFalse($this->latestVersion(self::CROCOD_PID)->shouldBeSearchable());
        $this->assertTrue($this->latestVersion(self::PRUK_PID)->shouldBeSearchable());
    }

    public function test_case_study_is_kept_out_of_elastic_but_the_pruk_dataset_is_not(): void
    {
        $crocodId = (string) Dataset::where('pid', self::CROCOD_PID)->value('id');
        $prukId = (string) Dataset::where('pid', self::PRUK_PID)->value('id');

        $this->assertNull((new IndexDataset($crocodId))->reindexElastic($crocodId, true));
        $this->assertIsArray((new IndexDataset($prukId))->reindexElastic($prukId, true));
    }
}
