<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use App\Models\DataProviderColl;
use Tests\Traits\MockExternalApis;
use ElasticClientController as ECC;
use App\Models\DataProviderCollHasTeam;

class DataCustodianNetworkTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v2/data_custodian_networks';

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    public function test_get_all_data_custodian_networks_with_success(): void
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
                    'summary',
                    'img_url',
                    'service',
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

    public function test_get_data_custodian_network_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'created_at',
                'updated_at',
                'deleted_at',
                'enabled',
                'name',
                'summary',
                'service',
                'img_url',
                'teams',
            ]
        ]);
        $content = $response->decodeResponseJson();

        $this->assertEquals($content['data']['img_url'], 'https://fakeimg.pl/300x200');
        $countTeams = count($content['data']['teams']);
        $this->assertTrue(($countTeams === 1));
    }

    public function test_get_data_custodian_network_info_with_success()
    {
        $id = DataProviderColl::where(['enabled' => 1])->first()->id;
        $response = $this->json('GET', self::TEST_URL . '/' . $id . '/info', [], $this->header);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'img_url',
                    'summary',
                    'service',
                    'enabled',

                ],
            ]);
    }

    public function test_get_data_custodian_network_custodians_summary_with_success()
    {
        $id = DataProviderColl::where(['enabled' => 1])->first()->id;
        $response = $this->json('GET', self::TEST_URL . '/' . $id . '/custodians_summary', [], $this->header);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'teams_counts' => [
                        0 => [
                            'id',
                            'teams_counts',
                        ]
                    ],
                ],
            ]);
    }

    public function test_get_data_custodian_network_datasets_summary_with_success()
    {
        $id = DataProviderColl::where(['enabled' => 1])->first()->id;
        $response = $this->json('GET', self::TEST_URL . '/' . $id . '/datasets_summary', [], $this->header);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'datasets_total',
                    'datasets',
                ],
            ]);
    }

    public function test_get_data_custodian_network_entities_summary_with_success()
    {
        $id = DataProviderColl::where(['enabled' => 1])->first()->id;
        $response = $this->json('GET', self::TEST_URL . '/' . $id . '/entities_summary', [], $this->header);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'durs_total',
                    'durs',
                    'tools_total',
                    'tools',
                    'publications_total',
                    'publications',
                    'collections_total',
                    'collections',
                ],
            ]);
    }

    public function test_get_data_custodian_network_info_summary_with_success()
    {
        $id = DataProviderColl::where(['enabled' => 1])->first()->id;
        $response = $this->json('GET', self::TEST_URL . '/' . $id . '/info', [], $this->header);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'img_url',
                    'summary',
                    'enabled',
                    'url',
                    'service',
                ],
            ]);
    }

    public function test_create_data_custodian_network_with_success(): void
    {

        ECC::shouldReceive("indexDocument")
            ->times(1);

        $payload = [
            'enabled' => true,
            'name' => 'Loki Data Provider',
            'summary' => fake()->text(255),
            'img_url' => 'https://doesntexist.com/img.jpeg',
            'team_ids' => [
                1,
                2,
                3,
            ],
            'service' => 'https://service.local/test',
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

        $relations = DataProviderCollHasTeam::where('data_provider_coll_id', $dpsId)->get();
        $this->assertNotNull($relations);
        $this->assertEquals(count($relations), 3);

    }

    public function test_update_data_provider_with_success(): void
    {
        ECC::shouldReceive("indexDocument")
            ->times(2);

        $payload = [
            'enabled' => true,
            'name' => 'Loki Data Provider',
            'summary' => fake()->text(255),
            'img_url' => 'https://doesntexist.com/img.jpeg',
            'team_ids' => [
                1,
                2,
                3,
            ],
            'service' => 'https://service.local/test',
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


    public function test_delete_data_custodian_network_with_success(): void
    {
        ECC::shouldReceive("indexDocument")
            ->times(1);
        ECC::shouldReceive("deleteDocument")
            ->times(1);

        $payload = [
            'enabled' => true,
            'name' => 'Loki Data Provider',
            'summary' => fake()->text(255),
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
            'summary' => fake()->text(255),
            'img_url' => 'https://doesntexist.com/img.jpeg',
            'team_ids' => [
                1,
                2,
                3,
            ],
            'service' => 'https://service.local/test',
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
