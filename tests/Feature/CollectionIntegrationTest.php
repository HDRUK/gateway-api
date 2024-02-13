<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Collection;

use App\Models\Application;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\DatasetVersionSeeder;
// use Illuminate\Foundation\Testing\WithFaker;
use Database\Seeders\KeywordSeeder;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\ApplicationSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\CollectionHasDatasetSeeder;
use Database\Seeders\CollectionHasKeywordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CollectionIntegrationTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    const TEST_URL = '/api/v1/integrations/collections';

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
            ApplicationSeeder::class,
            CollectionSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            KeywordSeeder::class,
            CollectionHasKeywordSeeder::class,
            CollectionHasDatasetSeeder::class,
        ]);

        $this->integration = Application::find(1)->first();
        $this->body = [
            "app_id" => $this->integration['app_id'], 
            "client_id" => $this->integration['client_id']
        ];
    }

    /**
     * Get All Collections with success
     * 
     * @return void
     */
    public function test_get_all_integration_collections_with_success(): void
    {
        $countCollection = Collection::count();
        $response = $this->json('GET', self::TEST_URL, $this->body, $this->header);

        $this->assertCount($countCollection, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'description',
                    'image_link',
                    'enabled',
                    'public',
                    'counter',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'datasets',
                    'keywords',
                    'users',
                    'applications',
                    'mongo_object_id',
                    'mongo_id',
                    'team',
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
        $response->assertStatus(200);
    }

    /**
     * Get Collection by Id with success
     * 
     * @return void
     */
    public function test_get_integration_collection_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', $this->body, $this->header);

        $this->assertCount(1, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'description',
                    'image_link',
                    'enabled',
                    'public',
                    'counter',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'mongo_object_id',
                    'mongo_id',
                    'datasets',
                    'keywords',
                    'users',
                    'applications',
                    'team',
                ]
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Create new Collection with success
     * 
     * @return void
     */
    public function test_add_new_integration_collection_with_success(): void
    {
        $countBefore = Collection::count();
        $mockData = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => "https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque",
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $this->generateDatasets(),
            "keywords" => $this->generateKeywords(),
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            array_merge($mockData, $this->body),
            $this->header
        );

        $countAfter = Collection::count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);
    }

    /**
     * Update Collection with sucess by id
     *
     * @return void
     */
    public function test_update_integration_collection_with_success(): void 
    {
        // create new collection
        $mockDataIns = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => "https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque",
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $this->generateDatasets(),
            "keywords" => $this->generateKeywords(),
        ];
        $responseIns = $this->json(
            'POST',
            self::TEST_URL,
            array_merge($mockDataIns, $this->body),
            $this->header
        );

        $responseIns->assertStatus(201);
        $idIns = (int) $responseIns['data'];

        // update collection
        $mockDataUpdate = [
            "name" => "covid update",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero. update",
            "image_link" => "https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque",
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $this->generateDatasets(),
            "keywords" => $this->generateKeywords(),
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $idIns,
            array_merge($mockDataUpdate, $this->body),
            $this->header
        );
        $responseUpdate->assertStatus(200);
        $this->assertTrue($mockDataUpdate['name'] === $responseUpdate['data']['name']);
        $this->assertTrue($mockDataUpdate['description'] === $responseUpdate['data']['description']);
        $this->assertTrue((bool) $mockDataUpdate['enabled'] === (bool) $responseUpdate['data']['enabled']);
        $this->assertTrue((bool) $mockDataUpdate['public'] === (bool) $responseUpdate['data']['public']);
        $this->assertTrue((int) $mockDataUpdate['counter'] === (int) $responseUpdate['data']['counter']);
    }

    /**
     * Edit Collection with sucess by id
     *
     * @return void
     */
    public function test_edit_integration_collection_with_success(): void
    {
        // create new collection
        $mockDataIns = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => "https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque",
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $this->generateDatasets(),
            "keywords" => $this->generateKeywords(),
        ];
        $responseIns = $this->json(
            'POST',
            self::TEST_URL,
            array_merge($mockDataIns, $this->body),
            $this->header
        );

        $responseIns->assertStatus(201);
        $idIns = (int) $responseIns['data'];

        // update collection
        $mockDataUpdate = [
            "name" => "covid update",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero. update",
            "image_link" => "https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque",
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $this->generateDatasets(),
            "keywords" => $this->generateKeywords(),
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $idIns,
            array_merge($mockDataUpdate, $this->body),
            $this->header
        );
        $responseUpdate->assertStatus(200);
        $this->assertTrue($mockDataUpdate['name'] === $responseUpdate['data']['name']);
        $this->assertTrue($mockDataUpdate['description'] === $responseUpdate['data']['description']);
        $this->assertTrue((bool) $mockDataUpdate['enabled'] === (bool) $responseUpdate['data']['enabled']);
        $this->assertTrue((bool) $mockDataUpdate['public'] === (bool) $responseUpdate['data']['public']);
        $this->assertTrue((int) $mockDataUpdate['counter'] === (int) $responseUpdate['data']['counter']);

        // edit
        $mockDataEdit1 = [
            "name" => "covid edit",
            "description" => "Nam dictum urna quis euismod lacinia.",
            "image_link" => "https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque",
        ];
        $responseEdit1 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $idIns,
            array_merge($mockDataEdit1, $this->body),
            $this->header
        );
        $responseEdit1->assertStatus(200);
        $this->assertTrue($mockDataEdit1['name'] === $responseEdit1['data']['name']);
        $this->assertTrue($mockDataEdit1['description'] === $responseEdit1['data']['description']);
        $this->assertTrue($mockDataEdit1['image_link'] === $responseEdit1['data']['image_link']);

        // edit
        $mockDataEdit2 = [
            "name" => "covid another edit",
            "counter" => 126
        ];
        $responseEdit2 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $idIns,
            array_merge($mockDataEdit2, $this->body),
            $this->header
        );
        $responseEdit2->assertStatus(200);
        $this->assertTrue($mockDataEdit2['name'] === $responseEdit2['data']['name']);
        $this->assertTrue((int) $mockDataEdit2['counter'] === (int) $responseEdit2['data']['counter']);

    }

    /**
     * SoftDelete Collection by Id with success
     *
     * @return void
     */
    public function test_soft_delete_integration_collection_with_success(): void
    {
        $countBefore = Collection::count();
        $countTrashedBefore = Collection::onlyTrashed()->count();
        // create new collection
        $mockDataIns = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => "https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque",
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $this->generateDatasets(),
            "keywords" => $this->generateKeywords(),
        ];
        $responseIns = $this->json(
            'POST',
            self::TEST_URL,
            array_merge($mockDataIns, $this->body),
            $this->header
        );

        $responseIns->assertStatus(201);
        $idIns = (int) $responseIns['data'];

        $countAfter = Collection::count();
        $this->assertTrue((bool) ($countAfter - $countBefore), 'Response was successfully');

        // delete collection
        $response = $this->json('DELETE', self::TEST_URL . '/' . $idIns, $this->body, $this->header);
        $response->assertStatus(200);
        $countTrasherAfter = Collection::onlyTrashed()->count();
        $this->assertTrue((bool) ($countTrasherAfter - $countTrashedBefore), 'Response was successfully');
    }

    private function generateKeywords()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $return[] = Keyword::where(['enabled' => 1])->get()->random()->name;
        }

        return array_unique($return);
    }

    private function generateDatasets()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $temp = [];
            $temp['id'] = Dataset::all()->random()->id;
            $temp['reason'] = htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8");
            $return[] = $temp;
        }

        return $return;
    }
}
