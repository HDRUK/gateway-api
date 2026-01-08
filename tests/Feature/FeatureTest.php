<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;
use App\Models\Feature as FeatureModel;

class FeatureTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/features';

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
     * Get All Features with success
     *
     * @return void
     */
    public function test_get_all_features_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);
        $responseData = $response->decodeResponseJson();

        $this->assertCount(FeatureModel::count(), $responseData['data']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'scope',
                    'value',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
    }

    public function test_get_feature_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);
        $responseData = $response->decodeResponseJson();

        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals(1, $responseData['data']['id']);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'scope',
                'value',
                'created_at',
                'updated_at',
            ],
        ]);
        $response->assertStatus(200);
    }
    /**
     * Create new Feature with success
     *
     * @return void
     */

    public function test_add_new_feature_with_success(): void
    {
        $countBefore = FeatureModel::count();

        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            [
                'name' => 'fake_for_test',
                'scope' => '__laravel_null',
                'value' => 'true',
            ],
            $this->header
        );

        $countAfter = FeatureModel::count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);
    }
    /**
     * Edit Feature with success
     *
     * @return void
     */
    public function test_edit_feature_with_success(): void
    {
        // create
        $countBefore = FeatureModel::count();

        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'fake_for_test',
                'scope' => '__laravel_null',
                'value' => 'true',
            ],
            $this->header
        );

        $countAfter = FeatureModel::count();
        $countNewRow = $countAfter - $countBefore;

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $responseCreate->assertStatus(201);

        $id = $contentCreate['data'];
        // edit
        $responseEdit1 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'name' => 'fake_for_test_e1',
            ],
            $this->header
        );

        $responseEdit1->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentEdit1 = $responseEdit1->decodeResponseJson();
        $this->assertEquals($contentEdit1['data']['name'], 'fake_for_test_e1');

        // edit
        $responseEdit2 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'name' => 'fake_for_test_e2',
                'value' => 'false',
            ],
            $this->header
        );

        $responseEdit2->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentEdit2 = $responseEdit2->decodeResponseJson();
        $this->assertEquals($contentEdit2['data']['name'], 'fake_for_test_e2');
        $this->assertEquals($contentEdit2['data']['value'], false);
    }

     
}
