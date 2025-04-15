<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Jobs\LinkageExtraction;
use App\Jobs\TermExtraction;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\MinimalUserSeeder;

class AdminDatasetControllerTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }
    public const TEST_URL_DATASET = '/api/v1/datasets/admin_ctrl';

    public function setUp(): void
    {
        $this->commonSetUp();
        // $this->seed([
        //     MinimalUserSeeder::class,
        //     DatasetSeeder::class,
        //     DatasetVersionSeeder::class,
        // ]);
    }

    public function testTriggerTermExtractionWithDefaults()
    {
        // Arrange: Ensure the TermExtraction job is queued
        Queue::fake();

        $allDatasetIds = Dataset::select('id')->pluck('id')->toArray();

        // Act: Call the endpoint without parameters to use defaults
        $response = $this->json(
            'POST',
            self::TEST_URL_DATASET . '/trigger/term_extraction',
            [],
            $this->header
        );

        if (config("ted.enabled")) {
            $response->assertStatus(200);

            // Assert: Check that the job was dispatched and response is correct
            Queue::assertPushed(TermExtraction::class);
            $response->assertJson([
                        'message' => 'triggered ted',
                        'dataset_ids' => $allDatasetIds
                    ]);
        } else {
            $response->assertStatus(500);
        }
    }

    public function testTriggerTermExtractionWithCustomIds()
    {
        // Arrange: Ensure the TermExtraction job is queued
        Queue::fake();

        // Act: Call the endpoint with custom minId and maxId
        $response = $this->json(
            'POST',
            self::TEST_URL_DATASET . '/trigger/term_extraction',
            ['minId' => 3, 'maxId' => 5],
            $this->header
        );
        if (config("ted.enabled")) {
            // Assert: Check that the job was dispatched and response is correct
            Queue::assertPushed(TermExtraction::class);
            $response->assertStatus(200)
                     ->assertJson([
                         'message' => 'triggered ted',
                         'dataset_ids' => [3, 4, 5],
                     ]);
        } else {
            $response->assertStatus(500);
        }
    }

    public function testTriggerTermExtractionHandlesUnauthorised()
    {
        //no token
        $response = $this->json('POST', self::TEST_URL_DATASET . '/trigger/term_extraction', []);
        $response->assertStatus(401);

        //create header with token for non-super admin
        $this->authorisationUser(false);
        $jwt = $this->getAuthorisationJwt(false);
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];

        $response = $this->json('POST', self::TEST_URL_DATASET . '/trigger/term_extraction', [], $header);
        $response->assertStatus(401);

        //token in header for super-admin
        $response = $this->json('POST', self::TEST_URL_DATASET . '/trigger/term_extraction', [], $this->header);
        if (config("ted.enabled")) {
            $response->assertStatus(200);
        } else {
            $response->assertStatus(500);
        }

    }

    public function testTriggerLinkageExtractionWithDefaults()
    {
        Queue::fake();

        $allDatasetIds = Dataset::select('id')->pluck('id')->toArray();

        $response = $this->json(
            'POST',
            self::TEST_URL_DATASET . '/trigger/linkage_extraction',
            [],
            $this->header
        );
        $response->assertStatus(200);

        Queue::assertPushed(LinkageExtraction::class);
        $response->assertJson([
                    'message' => 'triggered linkage',
                    'dataset_ids' => $allDatasetIds
                ]);

    }

}
