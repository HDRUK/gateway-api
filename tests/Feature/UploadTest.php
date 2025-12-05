<?php

namespace Tests\Feature;

use App\Models\Dur;
use Tests\TestCase;
use App\Models\Team;
use App\Models\Upload;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\DataAccessApplication;
use App\Models\QuestionBank;
use Tests\Traits\Authorization;
use Illuminate\Http\UploadedFile;
use Tests\Traits\MockExternalApis;

class UploadTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/files';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * Upload a file with success
     *
     * @return void
     */
    public function test_upload_file_with_success(): void
    {
        $file = new UploadedFile(
            getcwd() . '/tests/Unit/test_files/test_file.csv',
            'test_file.csv',
        );

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
        $file = new UploadedFile(
            getcwd() . '/tests/Unit/test_files/test_file.csv',
            'test_file.csv',
        );
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

        $id = $response->decodeResponseJson()['data']['uuid'];

        $response = $this->json('GET', self::TEST_URL . '/' . $id, [], $this->header);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'uuid',
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
        $file = new UploadedFile(
            getcwd() . '/tests/Unit/test_files/test_file.csv',
            'test_file.csv',
        );
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

        $uuid = $response->decodeResponseJson()['data']['uuid'];
        $id = $response->decodeResponseJson()['data']['id'];


        $upload = Upload::findOrFail($id);
        $upload->update([
            'status' => 'PENDING'
        ]);
        $response = $this->json(
            'GET',
            self::TEST_URL . '/processed' . '/' . $uuid . '/download',
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
            self::TEST_URL . '/processed' . '/' . $uuid . '/download',
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
            self::TEST_URL . '/processed' . '/' . $uuid . '/download',
            [],
            $this->header
        );
        $response->assertStatus(200);
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
            self::TEST_URL . '?entity_flag=dataset-from-upload&input_schema=GWDM&input_version=2.0&team_id=' . $team,
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
                'uuid',
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
        $file = new UploadedFile(
            getcwd() . '/tests/Unit/test_files/StructuralMetadataTemplate.xlsx',
            'StructuralMetadataTemplate.xlsx',
        );
        // post file to files endpoint
        $response = $this->json(
            'POST',
            self::TEST_URL . '?entity_flag=structural-metadata-upload',
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

        $content = $response->decodeResponseJson()['data'];
        $this->assertEquals($content['status'], 'PROCESSED');
        $this->assertEquals($content['error'], null);

        $response->assertStatus(200);
        $id = $response->decodeResponseJson()['data']['uuid'];

        $response = $this->json('GET', self::TEST_URL . '/' . $id, [], $this->header);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'uuid',
                'created_at',
                'updated_at',
                'filename',
                'file_location',
                'user_id',
                'status',
                'structural_metadata',
                'error'
            ]
        ]);
        $response->assertStatus(200);

        $content = $response->decodeResponseJson()['data'];

        $this->assertIsArray($content['structural_metadata']);
        $this->assertEquals($content['structural_metadata'][0]['name'], 'Test Table');
        $this->assertIsArray($content['structural_metadata'][0]['columns']);
        $this->assertEquals(
            $content['structural_metadata'][0]['columns'][0]['name'],
            'Test Column'
        );
    }

    public function test_accessing_files_from_different_users()
    {
        $file = new UploadedFile(
            getcwd() . '/tests/Unit/test_files/test_file.csv',
            'test_file.csv',
        );
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

        $uuid = $response->decodeResponseJson()['data']['uuid'];
        $id = $response->decodeResponseJson()['data']['id'];
        $upload = Upload::findOrFail($id);

        $upload->update([
            'status' => 'PROCESSED'
        ]);
        $response = $this->json(
            'GET',
            self::TEST_URL . '/processed' . '/' . $uuid . '/download',
            [],
            $this->header
        );
        $response->assertStatus(200);

        // Try accessing as a different user
        $this->authorisationUser(false, 2);
        $jwt = $this->getAuthorisationJwt(false, 2);
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
        $response = $this->json(
            'GET',
            self::TEST_URL . '/processed' . '/' . $uuid,
            [],
            $header
        );
        $response->assertStatus(500);

    }

    /**
     * Upload a team image with success
     *
     * @return void
     */
    public function test_team_logo_from_upload_with_success(): void
    {
        $teamId = Team::all()->random()->id;
        $file = UploadedFile::fake()->image('team_logo.jpeg', 600, 300);

        // post file to files endpoint
        $response = $this->json(
            'POST',
            self::TEST_URL . '?entity_flag=teams-media&team_id=' . $teamId,
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
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

        $this->assertEquals($content['data']['status'], 'PROCESSED');
        $this->assertNull($content['data']['error']);

        $team = Team::findOrFail($teamId);

        $this->assertTrue(str_contains($team->team_logo, 'team_logo.jpeg'));

        // restore null logo to team we used
        $team->update(['team_logo' => null]);
    }

    /**
     * Upload a team image with failure
     *
     * @return void
     */
    public function test_team_logo_from_upload_failure(): void
    {
        $teamId = Team::all()->random()->id;
        // test an image of the wrong size fails to upload
        $file = UploadedFile::fake()->image('team_logo.jpg', 400, 300);
        // post file to files endpoint
        $response = $this->json(
            'POST',
            self::TEST_URL . '?entity_flag=teams-media&team_id=' . $teamId,
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

        $this->assertEquals($content['data']['status'], 'FAILED');
        $this->assertNotNull($content['data']['error']);
    }

    /**
     * Upload a collection image with success
     *
     * @return void
     */
    public function test_collection_image_from_upload_with_success(): void
    {
        $collectionId = Collection::all()->random()->id;
        $file = UploadedFile::fake()->image('collection_image.jpg', 600, 300);
        // post file to files endpoint
        $response = $this->json(
            'POST',
            self::TEST_URL . '?entity_flag=collections-media&collection_id=' . $collectionId,
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

        $this->assertEquals($content['data']['status'], 'PROCESSED');
        $this->assertNull($content['data']['error']);

        $collection = Collection::findOrFail($collectionId);

        $this->assertTrue(str_contains($collection->image_link, 'collection_image.jpg'));

        // restore null link to collection we used
        $collection->update(['image_link' => null]);
    }

    /**
     * Upload a collection image with failure
     *
     * @return void
     */
    public function test_collection_image_from_upload_failure(): void
    {
        $collectionId = Collection::all()->random()->id;
        // test an image of the wrong size fails to upload
        $file = UploadedFile::fake()->image('collection_image.jpg', 400, 300);
        // post file to files endpoint
        $response = $this->json(
            'POST',
            self::TEST_URL . '?entity_flag=collections-media&collection_id=' . $collectionId,
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

        $this->assertEquals($content['data']['status'], 'FAILED');
        $this->assertNotNull($content['data']['error']);
    }

    /**
     * Upload a file for a dar application with success
     *
     * @return void
     */
    public function test_dar_application_upload_with_success(): void
    {
        $applicationId = DataAccessApplication::all()->random()->id;
        $questionId = QuestionBank::all()->random()->id;
        $file = UploadedFile::fake()->create('test_dar_application.pdf');
        // post file to files endpoint
        $response = $this->json(
            'POST',
            self::TEST_URL . '?entity_flag=dar-application-upload&application_id=' . $applicationId . '&question_id=' . $questionId,
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
                'entity_id',
                'question_id',
                'error',
            ]
        ]);
        $response->assertStatus(200);
        $content = $response->decodeResponseJson();
        $entityId = $content['data']['entity_id'];
        $questionIdResp = $content['data']['question_id'];

        $this->assertTrue($entityId === $applicationId);
        $this->assertTrue($questionIdResp === $questionId);
    }
}
