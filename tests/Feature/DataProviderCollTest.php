<?php

namespace Tests\Feature;

use Tests\TestCase;
use Database\Seeders\DurSeeder;
use Database\Seeders\TagSeeder;
use App\Models\DataProviderColl;
use Database\Seeders\TeamSeeder;
use Database\Seeders\ToolSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\KeywordSeeder;
use Database\Seeders\LicenseSeeder;
use ElasticClientController as ECC;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CollectionSeeder;
use App\Models\DataProviderCollHasTeam;
use Database\Seeders\ApplicationSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\PublicationSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Database\Seeders\TypeCategorySeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\CollectionHasDurSeeder;
use Database\Seeders\CollectionHasToolSeeder;
use Database\Seeders\CollectionHasUserSeeder;
use Database\Seeders\DataProviderCollsSeeder;
use Database\Seeders\CollectionHasKeywordSeeder;

use Database\Seeders\CollectionHasPublicationSeeder;
use Database\Seeders\CollectionHasDatasetVersionSeeder;
use Database\Seeders\PublicationHasDatasetVersionSeeder;

class DataProviderCollTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/data_provider_colls';

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            TeamSeeder::class,
            TeamHasUserSeeder::class,
            DataProviderCollsSeeder::class,
            ApplicationSeeder::class,
            CollectionSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            KeywordSeeder::class,
            CategorySeeder::class,
            TypeCategorySeeder::class,
            LicenseSeeder::class,
            ToolSeeder::class,
            TagSeeder::class,
            DurSeeder::class,
            CollectionHasKeywordSeeder::class,
            CollectionHasDatasetVersionSeeder::class,
            CollectionHasToolSeeder::class,
            CollectionHasDurSeeder::class,
            PublicationSeeder::class,
            PublicationHasDatasetVersionSeeder::class,
            CollectionHasPublicationSeeder::class,
            CollectionHasUserSeeder::class,
        ]);
    }

    public function test_get_all_data_provider_colls_with_success(): void
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

    public function test_get_data_provider_coll_by_id_with_success(): void
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


    public function test_get_data_provider_coll_by_id_summary_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1/summary', [], $this->header);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'name',
                'img_url',
                'summary',
                'service',
                'datasets',
                'durs',
                'tools',
                'publications',
                'collections',
            ]
        ]);
    }

    public function test_data_provider_collection_summary()
    {
        $id = DataProviderColl::where(['enabled' => 1])->first()->id;
        $response = $this->get('api/v1/data_provider_colls/' . $id . '/summary', [], $this->header);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'img_url',
                    'summary',
                    'service',
                    'enabled',
                    'teams_counts' => [
                        0 => [
                            'name',
                            'id',
                            'datasets_count',
                            'tools_count',
                            'durs_count',
                            'publications_count',
                            'collections_count'
                        ]
                    ],
                    'datasets_total',
                    'datasets',
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

    public function test_create_data_provider_coll_with_success(): void
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


    public function test_delete_data_provider_coll_with_success(): void
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
