<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;

use App\Models\Dataset;
use App\Models\Dur;
use App\Models\Team;
use App\Models\Upload;
use Tests\Traits\Authorization;

use Database\Seeders\DatasetSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\SpatialCoverageSeeder;

use MetadataManagementController AS MMC;

use Tests\Traits\MockExternalApis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    const TEST_URL = '/api/v1/files';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
        ]);
    }

    /**
     * Upload a file with success
     * 
     * @return void
     */
    public function test_upload_file_with_success(): void
    {
        $file = UploadedFile::fake()->create('test_file.csv');
        // post file to files endpoint
        $response = $this->json(
            'POST', 
            self::TEST_URL, 
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        
        $response->assertJsonStructure([
            'data' => [
                'id',
                'created_at',
                'updated_at',
                'filename',
                'file_location',
                'user_id',
                'status', 
                'error'
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Show an upload with success
     * 
     * @return void
     */
    public function test_show_upload_with_success(): void
    {
        $file = UploadedFile::fake()->create('test_file.csv');
        // post file to files endpoint
        $response = $this->json(
            'POST', 
            self::TEST_URL, 
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        
        $id = $response->decodeResponseJson()['data']['id'];

        $response = $this->json('GET', self::TEST_URL . '/' . $id, [], $this->header);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'created_at',
                'updated_at',
                'filename',
                'file_location',
                'user_id',
                'status', 
                'error'
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Retrieve file content with success
     * 
     * @return void
     */
    public function test_retrieve_file_content_with_success(): void
    {
        $file = UploadedFile::fake()->create('test_file.csv');
        // post file to files endpoint
        $response = $this->json(
            'POST', 
            self::TEST_URL, 
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        
        $id = $response->decodeResponseJson()['data']['id'];

        $upload = Upload::findOrFail($id);
        $upload->update([
            'status' => 'PENDING'
        ]);
        $response = $this->json(
            'GET',
            self::TEST_URL . '/processed' . '/' . $id,
            [],
            $this->header
        );
        $response->assertJsonStructure(['message']);
        $this->assertEquals($response['message'], 'File scan is pending');

        $upload->update([
            'status' => 'FAILED'
        ]);
        $response = $this->json(
            'GET',
            self::TEST_URL . '/processed' . '/' . $id,
            [],
            $this->header
        );
        $response->assertJsonStructure(['message']);
        $this->assertEquals($response['message'], 'File failed scan, content cannot be retrieved');

        $upload->update([
            'status' => 'PROCESSED'
        ]);
        $response = $this->json(
            'GET',
            self::TEST_URL . '/processed' . '/' . $id,
            [],
            $this->header
        );
        $response->assertJsonStructure([
            'message',
            'data' => [
                'filename',
                'content'
            ]
        ]);
        $this->assertEquals($response['message'], 'success');
        $this->assertNotNull($response['data']['content']);
    }

    /**
     * Upload a dur with success
     * 
     * @return void
     */
    public function test_dur_from_upload_with_success(): void
    {
        $countBefore = Dur::count();
        $team = Team::all()->random()->id;
        $file = new UploadedFile(
            getcwd() . '/tests/Unit/test_files/DataUseUploadTemplate_v2.xlsx', 
            'DataUseUploadTemplate_v2.xlsx',
        );
        // post file to files endpoint
        $response = $this->json(
            'POST', 
            self::TEST_URL . '?entity_flag=dur-from-upload&team_id=' . $team, 
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        
        $response->assertJsonStructure([
            'data' => [
                'id',
                'created_at',
                'updated_at',
                'filename',
                'file_location',
                'user_id',
                'status', 
                'error'
            ]
        ]);
        $response->assertStatus(200);
        $content = $response->decodeResponseJson();
        $durId = $content['data']['entity_id'];

        $countAfter = Dur::count();

        $this->assertTrue($countAfter - $countBefore === 1);

        $dur = Dur::findOrFail($durId);

        $this->assertEquals($dur->team_id, $team);
        $this->assertEquals($dur->organisation_name, "Test");
        $this->assertIsArray($dur->non_gateway_applicants);
    }

    /**
     * Upload a dataset from file with success
     * 
     * @return void
     */
    public function test_dataset_from_upload_with_success(): void
    {
        $countBefore = Dataset::count();
        $team = Team::all()->random()->id;
        $file = new UploadedFile(
            getcwd() . '/tests/Unit/test_files/gwdm_v2_uploaded.json', 
            'gwdm_v2_uploaded.json',
        );
        // post file to files endpoint
        $response = $this->json(
            'POST', 
            self::TEST_URL . '?entity_flag=dataset-from-upload&team_id=' . $team, 
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        
        $response->assertJsonStructure([
            'data' => [
                'id',
                'created_at',
                'updated_at',
                'filename',
                'file_location',
                'user_id',
                'status', 
                'error'
            ]
        ]);
        $response->assertStatus(200);
        $content = $response->decodeResponseJson();
        $datasetId = $content['data']['entity_id'];

        $countAfter = Dataset::count();

        $this->assertTrue($countAfter - $countBefore === 1);

        $dataset = Dataset::findOrFail($datasetId);

        $this->assertEquals($dataset->team_id, $team);
    }

    /**
     * Upload structural metadata from file with success
     * 
     * @return void
     */
    public function test_structural_metadata_from_upload_with_success(): void
    {
        $countBefore = Dataset::count();
        $dataset = Dataset::all()->random()->id;
        $file = new UploadedFile(
            getcwd() . '/tests/Unit/test_files/StructuralMetadataTemplate.xlsx', 
            'StructuralMetadataTemplate.xlsx',
        );
        // post file to files endpoint
        $response = $this->json(
            'POST', 
            self::TEST_URL . '?entity_flag=structural-metadata-upload&dataset_id=' . $dataset, 
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        
        $response->assertJsonStructure([
            'data' => [
                'id',
                'created_at',
                'updated_at',
                'filename',
                'file_location',
                'user_id',
                'status', 
                'error'
            ]
        ]);
        $response->assertStatus(200);
        $content = $response->decodeResponseJson();
        $datasetId = $content['data']['entity_id'];

        $this->assertEquals($datasetId, $dataset);

        // Grab the dataset that was just updated
        $response = $this->json('GET', '/api/v1/datasets' . '/' . $datasetId, [], $this->header);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'named_entities',
                'collections',
                'publications',
                'versions',
                'durs_count',
                'publications_count',
            ]
        ]);
        $response->assertStatus(200);

        // Get the latest version and check that the structural metadata matches test data
        $latestVersion = $response->decodeResponseJson()['data']['versions'][0]['metadata'];
        $structualMetadata = $latestVersion['metadata']['structuralMetadata'];
        dump($structualMetadata);
        $this->assertIsArray($latestVersion['metadata']['structuralMetadata']);
        $this->assertEquals(
            $latestVersion['metadata']['structuralMetadata'][0]['name'], 'Test Table'
        );
        $this->assertIsArray(
            $latestVersion['metadata']['structuralMetadata'][0]['columns']
        );
        $this->assertEquals(
            $latestVersion['metadata']['structuralMetadata'][0]['columns'][0]['name'],
            'Test Column'
        );        
    }
}
