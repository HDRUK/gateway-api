<?php

namespace Tests\Feature\Console;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Config;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\Helpers;
use Tests\Traits\MockExternalApis;

class DecodeDatasetMetadataEntitiesTest extends TestCase
{
    use Authorization;
    use Helpers;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_DATASET_V2 = '/api/v2/datasets';

    private int $teamId;

    private int $userId;

    protected function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $notificationID = $this->createNotification();
        $this->teamId = $this->createTeam([], [$notificationID]);
        $this->userId = $this->createUser();
    }

    private function createDatasetWithCorruptedAbstract(string $corruptedAbstract): int
    {
        $teamId = $this->teamId;
        $userId = $this->userId;
        $metadata = $this->getMetadata();

        $response = $this->json(
            'POST',
            self::TEST_URL_DATASET_V2,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $datasetId = $response->decodeResponseJson()['data'];

        $version = DatasetVersion::where('dataset_id', $datasetId)->firstOrFail();
        $corrupted = $version->metadata;
        $corrupted['metadata']['summary']['abstract'] = $corruptedAbstract;
        DatasetVersion::where('id', $version->id)->update(['metadata' => $corrupted]);

        return $datasetId;
    }

    private function getStoredAbstract(int $datasetId): string
    {
        $version = DatasetVersion::where('dataset_id', $datasetId)->firstOrFail();

        return $version->metadata['metadata']['summary']['abstract'];
    }

    public function test_decodes_multiply_encoded_abstract(): void
    {
        $datasetId = $this->createDatasetWithCorruptedAbstract(
            "BHF Data Science Centre&amp;amp;amp;amp;amp;amp;#039;s CVD-COVID-UK consortium"
        );

        Artisan::call('app:decode-dataset-metadata-entities');

        $this->assertEquals(
            "BHF Data Science Centre's CVD-COVID-UK consortium",
            $this->getStoredAbstract($datasetId)
        );
    }

    public function test_is_idempotent_on_already_clean_data(): void
    {
        $datasetId = $this->createDatasetWithCorruptedAbstract("Centre's data");

        Artisan::call('app:decode-dataset-metadata-entities');
        $firstPass = $this->getStoredAbstract($datasetId);

        Artisan::call('app:decode-dataset-metadata-entities');
        $secondPass = $this->getStoredAbstract($datasetId);

        $this->assertEquals("Centre's data", $firstPass);
        $this->assertEquals($firstPass, $secondPass);
    }

    public function test_dry_run_does_not_write_changes(): void
    {
        $datasetId = $this->createDatasetWithCorruptedAbstract(
            "Centre&amp;#039;s data"
        );

        Artisan::call('app:decode-dataset-metadata-entities', ['--dry-run' => true]);

        $this->assertEquals(
            "Centre&amp;#039;s data",
            $this->getStoredAbstract($datasetId)
        );
    }

    public function test_dataset_id_option_scopes_the_run(): void
    {
        $inScopeId = $this->createDatasetWithCorruptedAbstract("In scope&#039;s abstract");
        $outOfScopeId = $this->createDatasetWithCorruptedAbstract("Out of scope&#039;s abstract");

        Artisan::call('app:decode-dataset-metadata-entities', [
            '--dataset-id' => [(string) $inScopeId],
        ]);

        $this->assertEquals("In scope's abstract", $this->getStoredAbstract($inScopeId));
        $this->assertEquals("Out of scope&#039;s abstract", $this->getStoredAbstract($outOfScopeId));
    }

    public function test_decodes_title_and_short_title_columns(): void
    {
        $datasetId = $this->createDatasetWithCorruptedAbstract('Clean abstract');

        $version = DatasetVersion::where('dataset_id', $datasetId)->firstOrFail();
        DatasetVersion::where('id', $version->id)->update([
            'title' => 'Genetics of Asthma Severity &amp; Phenotypes',
            'short_title' => 'Genetics of Asthma Severity &amp; Phenotypes',
        ]);

        Artisan::call('app:decode-dataset-metadata-entities');

        $version->refresh();
        $this->assertEquals('Genetics of Asthma Severity & Phenotypes', $version->title);
        $this->assertEquals('Genetics of Asthma Severity & Phenotypes', $version->short_title);
    }
}
