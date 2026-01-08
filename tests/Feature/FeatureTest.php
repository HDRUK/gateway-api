<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\MockExternalApis;
use App\Models\Feature as FeatureModel;

class FeatureTest extends TestCase
{
    use MockExternalApis { setUp as commonSetUp; }

    public const TEST_URL = '/api/v1/features';
    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
        FeatureModel::truncate(); // clean table before each test
    }

    /** @test */
    public function test_get_all_features(): void
    {
        $feature = FeatureModel::factory()->create([
            'name' => 'test_feature',
            'scope' => 'global',
            'value' => 'true',
        ]);

        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         0 => ['id','name','scope','value','created_at','updated_at']
                     ]
                 ]);
    }

    /** @test */
    public function test_get_feature_by_id(): void
    {
        $feature = FeatureModel::factory()->create([
            'name' => 'test_feature',
            'scope' => 'global',
            'value' => 'true',
        ]);

        $response = $this->json('GET', self::TEST_URL . '/' . $feature->id, [], $this->header);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => ['id','name','scope','value','created_at','updated_at']
                 ]);

        $this->assertEquals($feature->id, $response->decodeResponseJson()['data']['id']);
    }

    /** @test */
    public function test_toggle_feature(): void
{
    $feature = FeatureModel::factory()->create([
        'name' => 'test_feature',
        'scope' => 'global',
        'value' => 'true',
    ]);

    $response = $this->json('PUT', self::TEST_URL . '/' . $feature->id, [], $this->header);
    $response->assertStatus(200)
             ->assertJsonStructure(['data']);

    $responseData = $response->decodeResponseJson();
    $this->assertEquals('false', $responseData['data']['value']);

    // toggle again
    $response2 = $this->json('PUT', self::TEST_URL . '/' . $feature->id, [], $this->header);
    $response2->assertStatus(200);
    $responseData2 = $response2->decodeResponseJson();
    $this->assertEquals('true', $responseData2['data']['value']);
}

}

