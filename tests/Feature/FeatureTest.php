<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\Authorization;
use App\Models\Feature as FeatureModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FeatureTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL_FEATURE = '/api/v1/features';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
    }

    /**
     * Get All Features with success
     * 
     * @return void
     */
    public function test_get_all_features_with_success(): void
    {
        $countTag = FeatureModel::all()->count();
        $response = $this->json('GET', self::TEST_URL_FEATURE, [], $this->header);

        $this->assertCount($countTag, $response['data']);
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
        ]);
    }

    /**
     * Get All Features and generate exception
     * 
     * @return void
     */
    public function test_get_all_features_and_generate_exception(): void
    {
        $response = $this->json('GET', self::TEST_URL_FEATURE, [], []);
        $response->assertStatus(401);
    }

    /**
     * Get Feature by Id with success
     * 
     * @return void
     */
    public function test_get_feature_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL_FEATURE . '/1', [], $this->header);
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

    public function test_add_new_feature_with_success(): void
    {
        $countFeatureBefore = FeatureModel::all()->count();

        $jwt = $this->getAuthorisationJwt();
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
        $response = $this->json(
            'POST', 
            self::TEST_URL_FEATURE . '/', 
            [
                'name' => 'fake_for_test',
                'enabled' => true,
            ], 
            $header
        );

        $countFeatureAfter = FeatureModel::all()->count();
        $countNewRow = $countFeatureAfter - $countFeatureBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);
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
        $jwt = $this->getAuthorisationJwt();
        $header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
        $response = $this->json('DELETE', self::TEST_URL_FEATURE . '/' . $id, [], $header);

        $countFeaturegDeleted = FeatureModel::onlyTrashed()->where('id', $id)->count();

        $response->assertStatus(200);

        if ($countFeature && $countFeaturegDeleted) {
            $this->assertTrue(true, 'Response was successfully');
        } else {
            $this->assertTrue(false, 'Response was unsuccessfully');
        }
    }
}
