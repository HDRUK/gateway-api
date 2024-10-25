<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Jobs\TermExtraction;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\MinimalUserSeeder;

class AdminDatasetControllerTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }
    public const TEST_URL_DATASET = '/api/v1/datasets/admin_ctrl';

    protected function setUp(): void
    {
        $this->commonSetUp();
        $this->seed([
            MinimalUserSeeder::class,
            DatasetSeeder::class,
        ]);
    }

    public function testTriggerTermExtractionWithDefaults()
    {
        // Arrange: Ensure the TermExtraction job is queued
        Queue::fake();

        // Act: Call the endpoint without parameters to use defaults
        $response = $this->json('POST', self::TEST_URL_DATASET . '/trigger/term_extraction', [], $this->header);
        //dd($response);
        // Assert: Check that the job was dispatched and response is correct
        Queue::assertPushed(TermExtraction::class);
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'triggered ted',
                     'datasetIds' => [1, 10],
                 ]);
    }

    public function testTriggerTermExtractionWithCustomIds()
    {
        // Arrange: Ensure the TermExtraction job is queued
        Queue::fake();

        // Act: Call the endpoint with custom minId and maxId
        $response = $this->json('POST', '/api/v1/admin-dataset/trigger-term-extraction', [
            'minId' => 1,
            'maxId' => 10,
        ]);

        // Assert: Check that the job was dispatched and response is correct
        Queue::assertPushed(TermExtraction::class);
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'triggered ted',
                     'datasetIds' => [1, 10],
                 ]);
    }

    public function testTriggerTermExtractionWithPartialFalse()
    {
        // Arrange: Ensure the TermExtraction job is queued
        Queue::fake();

        // Act: Call the endpoint with partial set to false
        $response = $this->json('POST', '/api/v1/admin-dataset/trigger-term-extraction', [
            'partial' => false,
        ]);

        // Assert: Check that the job was dispatched with partial = false
        Queue::assertPushed(TermExtraction::class, function ($job) {
            return !$job->partial;
        });

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'triggered ted',
                 ]);
    }

    public function testTriggerTermExtractionHandlesException()
    {
        // Simulate an exception during the process by making the max ID unreachable
        Dataset::shouldReceive('max')->andThrow(new \Exception('Simulated exception'));

        // Act: Call the endpoint
        $response = $this->json('POST', '/api/v1/admin-dataset/trigger-term-extraction');

        // Assert: Verify response is an error
        $response->assertStatus(500);
    }
}
