<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Collection;
use Tests\Traits\Authorization;
// use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CollectionTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL = '/api/v1/collections';

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
     * Get All Collections with success
     * 
     * @return void
     */
    public function test_get_all_collections_with_success(): void
    {
        $countCollection = Collection::count();
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $this->assertCount($countCollection, $response['data']);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'name',
                    'description',
                    'image_link',
                    'enabled',
                    'keywords',
                    'public',
                    'counter',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]
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
        $response->assertStatus(200);
    }

    /**
     * Get All Collections with no success
     * 
     * @return void
     */
    public function test_get_all_collections_and_generate_exception(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], []);
        $response->assertStatus(401);
    }

    /**
     * Get Collection by Id with success
     * 
     * @return void
     */
    public function test_get_collection_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);

        $this->assertCount(1, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'description',
                    'image_link',
                    'enabled',
                    'keywords',
                    'public',
                    'counter',
                    'created_at',
                    'updated_at',
                    'deleted_at',
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
    public function test_add_new_collection_with_success(): void
    {
        $countBefore = Collection::withTrashed()->count();
        $mockData = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => "https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque",
            "enabled" => true,
            "keywords" => "key words",
            "public" => true,
            "counter" => 123
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $mockData,
            $this->header
        );

        $countAfter = Collection::withTrashed()->count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);
    }

    /**
     * Update Collection with sucess by id
     *
     * @return void
     */
    public function test_update_collection_with_success(): void 
    {
        // create new collection
        $mockDataIns = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => "https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque",
            "enabled" => true,
            "keywords" => "key words",
            "public" => true,
            "counter" => 123
        ];
        $responseIns = $this->json(
            'POST',
            self::TEST_URL,
            $mockDataIns,
            $this->header
        );

        $responseIns->assertStatus(201);
        $idIns = (int) $responseIns['data'];

        // update collection
        $mockDataUpdate = [
            "name" => "covid 2",
            "description" => "Suscipit vitae mollitia molestias qui.",
            "image_link" => "https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque",
            "enabled" => false,
            "keywords" => "key words",
            "public" => false,
            "counter" => 125
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $idIns,
            $mockDataUpdate,
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
     * SoftDelete Collection by Id with success
     *
     * @return void
     */
    public function test_soft_delete_collection_with_success(): void
    {
        $countBefore = Collection::count();
        $countTrashedBefore = Collection::onlyTrashed()->count();
        // create new collection
        $mockDataIns = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => "https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque",
            "enabled" => true,
            "keywords" => "key words",
            "public" => true,
            "counter" => 123
        ];
        $responseIns = $this->json(
            'POST',
            self::TEST_URL,
            $mockDataIns,
            $this->header
        );

        $responseIns->assertStatus(201);
        $idIns = (int) $responseIns['data'];

        $countAfter = Collection::count();
        $this->assertTrue((bool) ($countAfter - $countBefore), 'Response was successfully');

        // delete collection
        $response = $this->json('DELETE', self::TEST_URL . '/' . $idIns, [], $this->header);
        $response->assertStatus(200);
        $countTrasherAfter = Collection::onlyTrashed()->count();
        $this->assertTrue((bool) ($countTrasherAfter - $countTrashedBefore), 'Response was successfully');
    }
}
