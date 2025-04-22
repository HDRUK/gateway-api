<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Database\Seeders\FeatureSeeder;
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

        $this->seed([
            FeatureSeeder::class,
        ]);
    }

    /**
     * Get All Features with success
     *
     * @return void
     */
    public function test_get_all_features_with_success(): void
    {
        $countTag = FeatureModel::where('enabled', 1)->count();
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $this->assertEquals($countTag, $response['total']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'enabled',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ],
            ],
            'current_page',
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
    }

    /**
     * Get Feature by Id with success
     *
     * @return void
     */
    public function test_get_feature_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);
        $this->assertCount(1, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'enabled',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]
            ]
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
        $countBefore = FeatureModel::all()->count();

        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            [
                'name' => 'fake_for_test',
                'enabled' => true,
            ],
            $this->header
        );

        $countAfter = FeatureModel::all()->count();
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
        $countBefore = FeatureModel::all()->count();

        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'fake_for_test',
                'enabled' => true,
            ],
            $this->header
        );

        $countAfter = FeatureModel::all()->count();
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
                'enabled' => false,
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
        $this->assertEquals($contentEdit2['data']['enabled'], false);
    }

    /**
     * SoftDelete Feature by Id with success
     *
     * @return void
     */
    public function test_soft_delete_feature_with_success(): void
    {
        $id = 1;
        $countFeature = FeatureModel::where('id', $id)->count();
        $response = $this->json('DELETE', self::TEST_URL . '/' . $id, [], $this->header);

        $countFeaturegDeleted = FeatureModel::onlyTrashed()->where('id', $id)->count();

        $response->assertStatus(200);

        if ($countFeature && $countFeaturegDeleted) {
            $this->assertTrue(true, 'Response was successfully');
        } else {
            $this->assertTrue(false, 'Response was unsuccessfully');
        }
    }
}
