<?php

namespace Tests\Feature;

use Config;
use App\Models\Dur;
use Tests\TestCase;
use App\Models\Tool;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Collection;
use App\Models\CollectionHasUser;
use App\Models\Publication;
use Database\Seeders\DurSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\ToolSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
// use Illuminate\Foundation\Testing\WithFaker;
use Database\Seeders\KeywordSeeder;
use Database\Seeders\LicenseSeeder;
use ElasticClientController as ECC;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\ApplicationSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\PublicationSeeder;
use Database\Seeders\TypeCategorySeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\CollectionHasDurSeeder;
use Database\Seeders\CollectionHasToolSeeder;
use Database\Seeders\CollectionHasUserSeeder;
use Database\Seeders\CollectionHasKeywordSeeder;
use Database\Seeders\DurHasDatasetVersionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Database\Seeders\CollectionHasPublicationSeeder;
use Database\Seeders\CollectionHasDatasetVersionSeeder;
use Database\Seeders\PublicationHasDatasetVersionSeeder;

class CollectionTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/collections';
    public const TEST_URL_V2 = '/api/v2/collections';

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
            CategorySeeder::class,
            TypeCategorySeeder::class,
            LicenseSeeder::class,
            ToolSeeder::class,
            TagSeeder::class,
            DurSeeder::class,
            DurHasDatasetVersionSeeder::class,
            CollectionHasKeywordSeeder::class,
            CollectionHasDatasetVersionSeeder::class,
            CollectionHasToolSeeder::class,
            CollectionHasDurSeeder::class,
            PublicationSeeder::class,
            PublicationHasDatasetVersionSeeder::class,
            CollectionHasPublicationSeeder::class,
            CollectionHasUserSeeder::class,
        ]);

        // Generate non-admin user for CREATOR
        $this->authorisationUser(false);
        $this->nonAdminJwt = $this->getAuthorisationJwt(false);
        $this->headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->nonAdminJwt,
        ];

        // generate jwt for a different user than the one who created the collection
        // This user can be used to test as COLLABORATOR or as non-collaborator
        $this->authorisationUser(false, 2);
        $this->nonAdmin2Jwt = $this->getAuthorisationJwt(false, 2);
        $this->nonAdmin2User = $this->getUserFromJwt($this->nonAdmin2Jwt);
        $this->headerNonAdmin2 = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->nonAdmin2Jwt,
        ];

    }

    /**
     * Get All Collections with success
     *
     * @return void
     */
    public function test_get_all_collections_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

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
                    'keywords',
                    'datasets',
                    'tools',
                    'dur',
                    'publications',
                    'users',
                    'applications',
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
    public function test_get_collection_by_id_with_success(): void
    {
        $collectionId = (int) Collection::all()->random()->id;
        $response = $this->json('GET', self::TEST_URL . '/' . $collectionId, [], $this->header);

        $response->assertJsonStructure([
            'message',
            'data' => [
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
                'keywords',
                'dataset_versions',
                'tools',
                'dur',
                'publications',
                'team',
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Create new Collection with success
     *
     * @return void
     */
    public function test_add_new_active_collection_with_success(): void
    {

        ECC::shouldReceive("indexDocument")
            ->with(
                \Mockery::on(
                    function ($params) {
                        return $params['index'] === ECC::ELASTIC_NAME_COLLECTION;
                    }
                )
            )
            ->times(1);

        $datasets = $this->generateDatasets();
        $nActive = Dataset::whereIn("id", array_column($datasets, 'id'))
            ->where('status', Dataset::STATUS_ACTIVE)
            ->count();

        ECC::shouldReceive("indexDocument")
            ->with(
                \Mockery::on(
                    function ($params) {
                        return $params['index'] === ECC::ELASTIC_NAME_DATASET;
                    }
                )
            )
            ->times($nActive);


        $countBefore = Collection::count();
        $mockData = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $datasets,
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockData,
            $this->headerNonAdmin
        );

        $countAfter = Collection::count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);

    }

    public function test_add_new_draft_collection_with_success(): void
    {

        ECC::shouldReceive("indexDocument")
            ->with(
                \Mockery::on(
                    function ($params) {
                        return $params['index'] === ECC::ELASTIC_NAME_COLLECTION;
                    }
                )
            )
            ->times(0);

        $datasets = $this->generateDatasets();
        $nActive = Dataset::whereIn("id", array_column($datasets, 'id'))
            ->where('status', Dataset::STATUS_ACTIVE)
            ->count();

        ECC::shouldReceive("indexDocument")
            ->with(
                \Mockery::on(
                    function ($params) {
                        return $params['index'] === ECC::ELASTIC_NAME_DATASET;
                    }
                )
            )
            ->times($nActive);

        $mockData = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $datasets,
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "DRAFT"
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockData,
            $this->headerNonAdmin
        );
        $response->assertStatus(201);

    }

    /**
     * Update Collection with success by id
     *
     * @return void
     */
    public function test_update_collection_with_success(): void
    {

        $datasets = $this->generateDatasets();
        ECC::shouldReceive("indexDocument")
        ->with(
            \Mockery::on(
                function ($params) {
                    return $params['index'] === ECC::ELASTIC_NAME_COLLECTION;
                }
            )
        )
        ->times(3);

        ECC::shouldIgnoreMissing(); //ignore index on datasets


        // create new collection
        $mockDataIn = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $datasets,
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "ACTIVE",
            'collaborators' => [$this->nonAdmin2User['id']],
        ];
        $responseIn = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockDataIn,
            $this->headerNonAdmin
        );

        $responseIn->assertStatus(201);
        $idIn = (int) $responseIn['data'];

        $collectionHasUsers = CollectionHasUser::where(['collection_id' => $idIn])->count();
        // one creator (jwt) + one collaborators
        $this->assertTrue((int)$collectionHasUsers === 2);

        // update collection by CREATOR
        $mockDataUpdate = [
            "name" => "covid update",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero. update",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 1,
            "datasets" => $datasets,
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "ACTIVE",
            "collaborators" => [$this->nonAdmin2User['id']],
        ];

        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL_V2 . '/' . $idIn,
            $mockDataUpdate,
            $this->headerNonAdmin
        );

        $responseUpdate->assertStatus(200);
        $this->assertTrue($mockDataUpdate['name'] === $responseUpdate['data']['name']);
        $this->assertTrue($mockDataUpdate['description'] === $responseUpdate['data']['description']);
        $this->assertTrue((bool) $mockDataUpdate['enabled'] === (bool) $responseUpdate['data']['enabled']);
        $this->assertTrue((bool) $mockDataUpdate['public'] === (bool) $responseUpdate['data']['public']);
        $this->assertTrue((int) $mockDataUpdate['counter'] === (int) $responseUpdate['data']['counter']);

        // update collection by COLLABORATOR
        $mockDataUpdate = [
            "name" => "covid update by collaborator",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero. update by collaborator",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 1,
            "datasets" => $datasets,
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "ACTIVE",
            "collaborators" => [$this->nonAdmin2User['id']],
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL_V2 . '/' . $idIn,
            $mockDataUpdate,
            $this->headerNonAdmin2
        );

        $responseUpdate->assertStatus(200);
        $this->assertTrue($mockDataUpdate['name'] === $responseUpdate['data']['name']);
        $this->assertTrue($mockDataUpdate['description'] === $responseUpdate['data']['description']);
        $this->assertTrue((bool) $mockDataUpdate['enabled'] === (bool) $responseUpdate['data']['enabled']);
        $this->assertTrue((bool) $mockDataUpdate['public'] === (bool) $responseUpdate['data']['public']);
        $this->assertTrue((int) $mockDataUpdate['counter'] === (int) $responseUpdate['data']['counter']);
    }

    /**
     * Update Collection without success by id
     *
     * @return void
     */
    public function test_update_collection_without_success(): void
    {
        $datasets = $this->generateDatasets();

        // create new collection
        $mockDataIn = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $datasets,
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "ACTIVE",
            "collaborators" => [],
        ];
        $responseIn = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockDataIn,
            $this->headerNonAdmin
        );

        $responseIn->assertStatus(201);
        $idIn = (int) $responseIn['data'];

        // fail to update collection by non-COLLABORATOR
        $mockDataUpdate = [
            "name" => "covid update",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero. update",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 1,
            "datasets" => $datasets,
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "ACTIVE",
            "collaborators" => [$this->nonAdmin2User['id']]
        ];

        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL_V2 . '/' . $idIn,
            $mockDataUpdate,
            $this->headerNonAdmin2
        );

        $responseUpdate->assertStatus(401); // Unauthorized
    }

    /**
     * Update Collection with success by id
     *
     * @return void
     */
    public function test_update_collection_to_draft_with_success(): void
    {

        ECC::shouldReceive("indexDocument")
            ->with(
                \Mockery::on(
                    function ($params) {
                        return $params['index'] === ECC::ELASTIC_NAME_COLLECTION;
                    }
                )
            )
            ->times(1);

        ECC::shouldReceive("deleteDocument")->once();

        ECC::shouldIgnoreMissing(); //ignore index on datasets

        // create new collection
        $mockDataIns = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $this->generateDatasets(),
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "ACTIVE",
            "collaborators" => [$this->nonAdmin2User['id']],
        ];
        $responseIns = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockDataIns,
            $this->headerNonAdmin
        );

        $responseIns->assertStatus(201);
        $idIns = (int) $responseIns['data'];

        // update collection
        $mockDataUpdate = [
            "name" => "covid update",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero. update",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 1,
            "datasets" => $this->generateDatasets(),
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "DRAFT",
            'collaborators' => [$this->nonAdmin2User['id']],
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL_V2 . '/' . $idIns,
            $mockDataUpdate,
            $this->headerNonAdmin2
        );
        $responseUpdate->assertStatus(200);
    }

    /**
     * Edit Collection with success by id
     *
     * @return void
     */
    public function test_edit_collection_with_success(): void
    {
        // create new collection
        $mockDataIn = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $this->generateDatasets(),
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "ACTIVE",
            "collaborators" => [$this->nonAdmin2User['id']],
        ];
        $responseIn = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockDataIn,
            $this->headerNonAdmin
        );

        $responseIn->assertStatus(201);
        $idIn = (int) $responseIn['data'];

        // update collection
        $mockDataUpdate = [
            "name" => "covid update",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero. update",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 1,
            "datasets" => $this->generateDatasets(),
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "status" => "DRAFT",
            "collaborators" => [$this->nonAdmin2User['id']],
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL_V2 . '/' . $idIn,
            $mockDataUpdate,
            $this->headerNonAdmin
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
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
        ];
        $responseEdit1 = $this->json(
            'PATCH',
            self::TEST_URL_V2 . '/' . $idIn,
            $mockDataEdit1,
            $this->headerNonAdmin
        );
        $responseEdit1->assertStatus(200);
        $this->assertTrue($mockDataEdit1['name'] === $responseEdit1['data']['name']);
        $this->assertTrue($mockDataEdit1['description'] === $responseEdit1['data']['description']);
        $this->assertTrue($mockDataEdit1['image_link'] === $responseEdit1['data']['image_link']);

        // edit by CREATOR
        $mockDataEdit2 = [
            "name" => "covid another edit",
            "counter" => 126
        ];
        $responseEdit2 = $this->json(
            'PATCH',
            self::TEST_URL_V2 . '/' . $idIn,
            $mockDataEdit2,
            $this->headerNonAdmin
        );
        $responseEdit2->assertStatus(200);
        $this->assertTrue($mockDataEdit2['name'] === $responseEdit2['data']['name']);
        $this->assertTrue((int) $mockDataEdit2['counter'] === (int) $responseEdit2['data']['counter']);

        // edit by COLLABORATOR
        $mockDataEdit2 = [
            "name" => "covid another edit by collaborator",
            "counter" => 127
        ];
        $responseEdit2 = $this->json(
            'PATCH',
            self::TEST_URL_V2 . '/' . $idIn,
            $mockDataEdit2,
            $this->headerNonAdmin2
        );
        $responseEdit2->assertStatus(200);
        $this->assertTrue($mockDataEdit2['name'] === $responseEdit2['data']['name']);
        $this->assertTrue((int) $mockDataEdit2['counter'] === (int) $responseEdit2['data']['counter']);
    }


    /**
     * Edit Collection without success by id
     *
     * @return void
     */
    public function test_edit_collection_without_success(): void
    {
        // create new collection
        $mockDataIn = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $this->generateDatasets(),
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "ACTIVE",
        ];
        $responseIn = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockDataIn,
            $this->headerNonAdmin
        );

        $responseIn->assertStatus(201);
        $idIn = (int) $responseIn['data'];

        // update collection
        $mockDataUpdate = [
            "name" => "covid update",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero. update",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 1,
            "datasets" => $this->generateDatasets(),
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "status" => "DRAFT",
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL_V2 . '/' . $idIn,
            $mockDataUpdate,
            $this->headerNonAdmin
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
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
        ];

        $responseEdit1 = $this->json(
            'PATCH',
            self::TEST_URL_V2 . '/' . $idIn,
            $mockDataEdit1,
            $this->headerNonAdmin2 // Not a COLLABORATOR
        );

        $responseEdit1->assertStatus(401); // Unauthorized

    }

    /**
     * SoftDelete Collection by Id with success
     *
     * @return void
     */
    public function test_soft_delete_and_unarchive_collection_with_success(): void
    {
        ECC::shouldReceive("deleteDocument")
            ->times(1);

        //dont bother checking any indexing here upon creation
        ECC::shouldIgnoreMissing();

        $countBefore = Collection::count();
        $countTrashedBefore = Collection::onlyTrashed()->count();
        // create new collection
        $mockDataIn = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $this->generateDatasets(),
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "ACTIVE",
            "collaborators" => [$this->nonAdmin2User['id']],
        ];
        $responseIn = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockDataIn,
            $this->headerNonAdmin
        );
        $responseIn->assertStatus(201);
        $idIn = (int) $responseIn['data'];

        $countAfter = Collection::count();
        $this->assertEquals($countAfter, $countBefore + 1);

        // delete collection
        $response = $this->json(
            'DELETE',
            self::TEST_URL_V2 . '/' . $idIn,
            [],
            $this->headerNonAdmin
        );
        $response->assertStatus(200);

        $countAfter = Collection::count();
        $countTrashedAfter = Collection::onlyTrashed()->count();

        $this->assertEquals($countAfter, $countBefore);
        $this->assertEquals($countTrashedAfter, 1);

        $response = $this->json(
            'PATCH',
            self::TEST_URL_V2 . '/' . $idIn . '?unarchive',
            ['status' => 'ACTIVE'],
            $this->headerNonAdmin2
        );

        $response->assertStatus(200);

        $countTrashedAfterUnarchiving = Collection::onlyTrashed()->count();
        $countAfterUnarchiving = Collection::count();

        $this->assertEquals($countTrashedAfterUnarchiving, 0);
        $this->assertTrue($countAfter < $countAfterUnarchiving);
        $this->assertEquals($countBefore + 1, $countAfterUnarchiving);
    }


    /**
     * SoftDelete Collection by Id with success - index not deleted for draft
     *
     * @return void
     */
    public function test_does_not_delete_index_on_draft_archived_collection(): void
    {
        ECC::shouldReceive("deleteDocument")
            ->times(0);

        //dont bother checking any indexing here upon creation
        ECC::shouldIgnoreMissing();

        // create new draft collection
        $mockDataIn = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $this->generateDatasets(),
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "DRAFT",
            "collaborators" => [$this->nonAdmin2User['id']]
        ];
        $responseIn = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockDataIn,
            $this->headerNonAdmin
        );
        $responseIn->assertStatus(201);
        $idIn = (int) $responseIn['data'];

        $response = $this->json(
            'DELETE',
            self::TEST_URL_V2 . '/' . $idIn,
            [],
            $this->header
        );
        $response->assertStatus(200);
    }


    /**
     * SoftDelete Collection by Id without success
     *
     * @return void
     */
    public function test_soft_delete_collection_without_success(): void
    {
        $countBefore = Collection::count();

        // create new collection
        $mockDataIn = [
            "name" => "covid",
            "description" => "Dolorem voluptas consequatur nihil illum et sunt libero.",
            "image_link" => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            "enabled" => true,
            "public" => true,
            "counter" => 123,
            "datasets" => $this->generateDatasets(),
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "collaborators" => [],
        ];
        $responseIn = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockDataIn,
            $this->headerNonAdmin
        );
        $responseIn->assertStatus(201);
        $idIn = (int) $responseIn['data'];

        $countAfter = Collection::count();
        $this->assertEquals($countAfter, $countBefore + 1);

        // delete collection with user who is not a collaborator
        $response = $this->json(
            'DELETE',
            self::TEST_URL_V2 . '/' . $idIn,
            [],
            $this->headerNonAdmin2
        );
        $response->assertStatus(401);
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

    private function generateTools()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $temp = [];
            $temp['id'] = Tool::all()->random()->id;
            $temp['reason'] = htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8");
            $return[] = $temp;
        }

        return $return;
    }

    private function generateDurs()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $temp = [];
            $temp['id'] = Dur::all()->random()->id;
            $temp['reason'] = htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8");
            $return[] = $temp;
        }

        return $return;
    }

    private function generatePublications()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $temp = [];
            $temp['id'] = Publication::all()->random()->id;
            $temp['reason'] = htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8");
            $return[] = $temp;
        }

        return $return;
    }
}
