<?php

namespace Tests\Feature;

use Tests\TestCase;
use Laravel\Pennant\Feature;
use App\Models\Feature as FeatureModel;
use Tests\Traits\Authorization;



class FeatureTest extends TestCase
{
    use Authorization;
    public const TEST_URL = '/api/v1/features';

    public function setUp(): void
    {
        parent::setUp();
        $this->authorisationUser(true);
        $jwt = $this->getAuthorisationJwt(true);

        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
    }

    public function test_the_application_return_all_features(): void
    {
        $response = $this->json('GET', self::TEST_URL);

        $response->assertStatus(200);
        $this->assertArrayHasKey('data', $response);
    }

    public function test_the_application_cannot_get_feature_by_feature_id(): void
    {
        $latestFeature = FeatureModel::query()->orderBy('id', 'desc')->first();
        $featureIdTest = $latestFeature ? $latestFeature->id + 1 : 1;

         $response = $this->json(
                'GET',
                self::TEST_URL . "/{$featureIdTest}"
            );

        $response->assertStatus(400);
        $message = $response->decodeResponseJson()['message'];
        $this->assertEquals('Invalid argument(s)', $message);
    }

    public function test_the_application_cannot_toggle_feature_by_feature_id(): void
    {
        $latestFeature = FeatureModel::query()->orderBy('id', 'desc')->first();
        $featureIdTest = $latestFeature ? $latestFeature->id + 1 : 1;

        $response = $this->json(
            'PUT',
            self::TEST_URL . "/{$featureIdTest}",
            [],
            $this->header
        );


        $response->assertStatus(400);
        $message = $response->decodeResponseJson()['message'];
        $this->assertEquals('Invalid argument(s)', $message);
    }

    public function test_the_application_toggles_features_by_feature_id(): void
    {
        // false to true
        $feature = FeatureModel::factory()->create([
            'name' => fake()->unique()->slug(2),
            'value' => 'false',
            'scope' => '__laravel_null',
        ]);

        $this->assertEquals('false', Feature::active($feature->name));

        $response = $this->json(
            'PUT',
            self::TEST_URL . "/{$feature->id}",
            [],
            $this->header
        );

        $response->assertStatus(200);
        $this->assertEquals('true', Feature::active($feature->name));

        // true to false
        $response = $this->json(
            'PUT',
            self::TEST_URL . "/{$feature->id}",
            [],
            $this->header
        );
        $response->assertStatus(200);
        $this->assertEquals('false', Feature::active($feature->name));
    }
}
