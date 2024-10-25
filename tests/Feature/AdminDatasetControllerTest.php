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
use Database\Seeders\DatasetVersionSeeder;
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
            DatasetVersionSeeder::class,
        ]);
    }
    /**
     * @OA\Post(
     *     path="/datasets/admin_ctrl/trigger/term_extraction",
     *     summary="Trigger Term Extraction for Datasets",
     *     description="Triggers the term extraction job for datasets within a specified range and controls whether data is partially indexed in Elasticsearch.",
     *     tags={"Datasets"},
     *     security={{"jwt": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="partial",
     *                 type="boolean",
     *                 default=true,
     *                 description="Flag to determine if term extraction should be partial (true) or full (false)"
     *             ),
     *             @OA\Property(
     *                 property="minId",
     *                 type="integer",
     *                 default=1,
     *                 description="Minimum dataset ID to include in the term extraction"
     *             ),
     *             @OA\Property(
     *                 property="maxId",
     *                 type="integer",
     *                 description="Maximum dataset ID to include in the term extraction. Defaults to the maximum dataset ID available."
     *             ),
     *             @OA\Property(
     *                 property="indexElastic",
     *                 type="boolean",
     *                 default=true,
     *                 description="Flag to determine if data should be indexed in Elasticsearch"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Term extraction triggered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="triggered ted"),
     *             @OA\Property(property="datasetIds", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="An error message detailing the issue")
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="JWT token for authorization in the format 'Bearer {token}'"
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Role required to access this endpoint, e.g., 'hdruk.superadmin'"
     *     )
     * )
     */
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

        $content = $response->decodeResponseJson();
        $response->assertStatus(200);

        // Assert: Check that the job was dispatched and response is correct
        Queue::assertPushed(TermExtraction::class);
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'triggered ted',
                     'datasetIds' => $allDatasetIds
                 ]);
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

        // Assert: Check that the job was dispatched and response is correct
        Queue::assertPushed(TermExtraction::class);
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'triggered ted',
                     'datasetIds' => [3, 4, 5],
                 ]);
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
        $response->assertStatus(200);

    }

}
