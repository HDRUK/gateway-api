<?php

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\DataProvider;
use App\Models\DataProviderHasTeam;

use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Database\Seeders\DataProviderSeeder;

use Tests\Traits\Authorization;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\Traits\MockExternalApis;

class DataProviderTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    const TEST_URL = '/api/v1/data_providers';

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            TeamSeeder::class,
            TeamHasUserSeeder::class,
            DataProviderSeeder::class,
        ]);
    }

    public function test_get_all_data_providers_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'enabled',
                    'name',
                    'img_url',
                    'teams',
                ],
            ],
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

    public function test_get_data_provider_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);

        $response->assertStatus(200);
        $content = $response->decodeResponseJson();

        $this->assertEquals($content['data']['img_url'], 'https://fakeimg.pl/300x200');
        $countTeams = count($content['data']['teams']);
        $this->assertTrue(($countTeams === 1));
    }

    public function test_create_data_provider_with_success(): void
    {
        $payload = [
            'enabled' => true,
            'name' => 'Loki Data Provider',
            'img_url' => 'https://doesntexist.com/img.jpeg',
            'team_ids' => [
                1,
                2,
                3,
            ],
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $payload,
            $this->header,
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data',
        ]);

        $dpsId = $response->decodeResponseJson()['data'];

        $relations = DataProviderHasTeam::where('data_provider_id', $dpsId)->get();
        $this->assertNotNull($relations);
        $this->assertEquals(count($relations), 3);
    }

    public function test_update_data_provider_with_success(): void
    {
        $payload = [
            'enabled' => true,
            'name' => 'Loki Data Provider',
            'img_url' => 'https://doesntexist.com/img.jpeg',
            'team_ids' => [
                1,
                2,
                3,
            ],
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $payload,
            $this->header,
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data',
        ]);

        $dpsId = $response->decodeResponseJson()['data'];
        
        $payload['name'] = 'Loki Updated Data Provider';

        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $dpsId,
            $payload,
            $this->header,
        );

        $response->assertStatus(200);
        $content = $response->decodeResponseJson()['data'];

        $this->assertEquals($content['name'], 'Loki Updated Data Provider');
    }

    public function test_delete_data_provider_with_success(): void
    {
        $payload = [
            'enabled' => true,
            'name' => 'Loki Data Provider',
            'img_url' => 'https://doesntexist.com/img.jpeg',
            'team_ids' => [
                1,
                2,
                3,
            ],
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $payload,
            $this->header,
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data',
        ]);

        $dpsId = $response->decodeResponseJson()['data'];

        $response = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $dpsId,
            [],
            $this->header,
        );

        $response->assertStatus(200);
    }

    public function test_can_update_team_associations_with_success(): void
    {
        $payload = [
            'enabled' => true,
            'name' => 'Loki Data Provider',
            'img_url' => 'https://doesntexist.com/img.jpeg',
            'team_ids' => [
                1,
                2,
                3,
            ],
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $payload,
            $this->header,
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data',
        ]);

        $dpsId = $response->decodeResponseJson()['data'];

        // First confirm teams are as configured
        $response = $this->json(
            'GET',
            self::TEST_URL . '/' . $dpsId,
            [],
            $this->header,
        );

        $response->assertStatus(200);

        $content = $response->decodeResponseJson()['data'];

        $this->assertTrue(count($content['teams']) === 3);

        foreach ($content['teams'] as $team) {
            $this->assertTrue(in_array($team['id'], $payload['team_ids']));
        }

        $payload['team_ids'] = [ 2, 3 ];
        // Now re-associate team ids
        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $dpsId,
            $payload,
            $this->header,
        );

        $response->assertStatus(200);

        $response = $this->json(
            'GET',
            self::TEST_URL . '/' . $dpsId,
            [],
            $this->header,
        );
        
        $content = $response->decodeResponseJson()['data'];

        $this->assertTrue(count($content['teams']) === 2);

        foreach ($content['teams'] as $team) {
            $this->assertTrue(in_array($team['id'], $payload['team_ids']));
        }

    }
}