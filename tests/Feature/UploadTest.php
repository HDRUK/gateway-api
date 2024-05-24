<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;

use App\Models\Upload;
use Tests\Traits\Authorization;

use Database\Seeders\MinimalUserSeeder;

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
        
        var_dump($response->json());
        
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
        // $this->assertNotNull($response['data']['content']);
    }
}
