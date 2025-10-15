<?php

namespace Tests\Feature\V2;

use Config;
use App\Models\Dur;
use Tests\TestCase;
use App\Http\Enums\TeamMemberOf;
use App\Models\Tool;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Collection;
use App\Models\CollectionHasUser;
use App\Models\Publication;
use App\Models\User;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class CollectionTest extends TestCase
{
    use Authorization;

    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_V2 = '/api/v2/collections';

    protected $header = [];
    protected $nonAdminJwt;
    protected $nonAdmin2Jwt;
    protected $headerNonAdmin;
    protected $headerNonAdmin2;
    protected $nonAdmin2User;

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

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
        $response = $this->json('GET', self::TEST_URL_V2, [], $this->header);

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
     * Get All Collections counts with success
     *
     * @return void
     */
    public function test_get_collections_count_with_success(): void
    {
        // Ensure we have at least one active and one draft collection
        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockData,
            $this->headerNonAdmin
        );

        $mockData = [
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
            "status" => "DRAFT"
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockData,
            $this->headerNonAdmin
        );

        $response = $this->json('GET', self::TEST_URL_V2 . '/count/status', [], $this->header);

        $response->assertJsonStructure([
            'data' => [
                'ACTIVE',
                'DRAFT'
            ],
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
        $response = $this->json('GET', self::TEST_URL_V2 . '/' . $collectionId, [], $this->header);

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
        var_dump('begin test_add_new_active_collection_with_success()');
        $datasets = $this->generateDatasets();
        $nActive = Dataset::whereIn("id", array_column($datasets, 'id'))
            ->where('status', Dataset::STATUS_ACTIVE)
            ->count();

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

        var_dump('$this->headerNonAdmin', $this->headerNonAdmin);
        $response = $this->json(
            'POST',
            self::TEST_URL_V2,
            $mockData,
            $this->headerNonAdmin
        );

        $countAfter = Collection::count();
        $countNewRow = $countAfter - $countBefore;
        var_dump($response->decodeResponseJson());
        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);
        var_dump('end test_add_new_active_collection_with_success()');
    }

    public function test_add_new_draft_collection_with_success(): void
    {
        $datasets = $this->generateDatasets();
        $nActive = Dataset::whereIn("id", array_column($datasets, 'id'))
            ->where('status', Dataset::STATUS_ACTIVE)
            ->count();

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
            'collaborators' => [$this->nonAdmin2User['id']],
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

        $responseEdit1->assertStatus(200); // Unauthorized

    }

    /**
     * SoftDelete Collection by Id with success
     *
     * @return void
     */
    public function test_soft_delete_and_unarchive_collection_with_success(): void
    {
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
            'PATCH',
            self::TEST_URL_V2 . '/' . $idIn,
            [
                'status' => 'ARCHIVED',
            ],
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

    /**
     * Create new users Collection with success
     *
     * @return void
     */
    public function test_add_new_users_collection_with_success(): void
    {
        $datasets = $this->generateDatasets();
        $nActive = Dataset::whereIn("id", array_column($datasets, 'id'))
            ->where('status', Dataset::STATUS_ACTIVE)
            ->count();

        $countBefore = Collection::count();

        $collectionOwner = $this->createCollectionOwner();
        $ownerHeader = $collectionOwner['ownerHeader'];
        $ownerId = $collectionOwner['userId'];

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
            'api/v2/users/' . $ownerId . '/collections',
            $mockData,
            $ownerHeader
        );

        $countAfter = Collection::count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);

    }

    /**
     * Get users Collection with success
     *
     * @return void
     */
    public function test_get_users_collection_with_success(): void
    {
        $countBefore = Collection::count();

        $collectionOwner = $this->createCollectionOwner();
        $ownerHeader = $collectionOwner['ownerHeader'];
        $ownerId = $collectionOwner['userId'];

        // Create a collection with each status
        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            'api/v2/users/' . $ownerId . '/collections',
            $mockData,
            $ownerHeader
        );

        $mockData = [
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
            "status" => "DRAFT"
        ];

        $response = $this->json(
            'POST',
            'api/v2/users/' . $ownerId . '/collections',
            $mockData,
            $ownerHeader
        );

        $mockData = [
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
            "status" => "ARCHIVED"
        ];

        $response = $this->json(
            'POST',
            'api/v2/users/' . $ownerId . '/collections',
            $mockData,
            $ownerHeader
        );

        // Test get active collections
        $response = $this->json(
            'GET',
            'api/v2/users/' . $ownerId . '/collections/status/active',
            [],
            $ownerHeader
        );
        $response->assertStatus(200);
        $content = $response->decodeResponseJson();
        $this->assertEquals('ACTIVE', $content['data'][0]['status']);

        // Test get draft collections
        $response = $this->json(
            'GET',
            'api/v2/users/' . $ownerId . '/collections/status/draft',
            [],
            $ownerHeader
        );
        $response->assertStatus(200);
        $content = $response->decodeResponseJson();
        $this->assertEquals('DRAFT', $content['data'][0]['status']);

        // Test get active collections
        $response = $this->json(
            'GET',
            'api/v2/users/' . $ownerId . '/collections/status/archived',
            [],
            $ownerHeader
        );
        $response->assertStatus(200);
        $content = $response->decodeResponseJson();
        $this->assertEquals('ARCHIVED', $content['data'][0]['status']);

    }

    /**
     * Get users Collection by id with success
     *
     * @return void
     */
    public function test_get_users_collection_by_id_with_success(): void
    {
        $countBefore = Collection::count();

        $collectionOwner = $this->createCollectionOwner();
        $ownerHeader = $collectionOwner['ownerHeader'];
        $ownerId = $collectionOwner['userId'];

        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            'api/v2/users/' . $ownerId . '/collections',
            $mockData,
            $ownerHeader
        );
        $collectionId = $response->decodeResponseJson()['data'];

        // Test get collection by id
        $response = $this->json(
            'GET',
            'api/v2/users/' . $ownerId . '/collections/' . $collectionId,
            [],
            $ownerHeader
        );
        $response->assertJsonStructure([
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
                'users',
                'team',
            ],
        ]);
        $response->assertStatus(200);

        // Try to get collection through incorrect user endpoint
        $response = $this->json(
            'GET',
            'api/v2/users/' . $ownerId + 1 . '/collections/' . $collectionId,
            [],
            $ownerHeader
        );
        $response->assertStatus(401);
    }

    /**
     * Update users Collection with success
     *
     * @return void
     */
    public function test_update_users_collection_with_success(): void
    {
        $collectionOwner = $this->createCollectionOwner();
        $ownerHeader = $collectionOwner['ownerHeader'];
        $ownerId = $collectionOwner['userId'];

        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            'api/v2/users/' . $ownerId . '/collections',
            $mockData,
            $ownerHeader
        );

        $response->assertStatus(201);
        $collectionId = $response->decodeResponseJson()['data'];

        $updateData = [
            "name" => "updated name",
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
            "status" => "DRAFT"
        ];

        $response = $this->json(
            'PUT',
            'api/v2/users/' . $ownerId . '/collections/' . $collectionId,
            $updateData,
            $ownerHeader
        );

        $response->assertStatus(200);
        $content = $response->decodeResponseJson();

        $this->assertEquals('updated name', $content['data']['name']);
        $this->assertEquals('DRAFT', $content['data']['status']);

        // Test a different user cannot update
        $otherUser = $this->createCollectionOwner();
        $otherUserHeader = $otherUser['ownerHeader'];

        $response = $this->json(
            'PUT',
            'api/v2/users/' . $ownerId . '/collections/' . $collectionId,
            $updateData,
            $otherUserHeader
        );
        $response->assertStatus(401);
    }

    /**
     * Edit users Collection with success
     *
     * @return void
     */
    public function test_edit_users_collection_with_success(): void
    {
        $collectionOwner = $this->createCollectionOwner();
        $ownerHeader = $collectionOwner['ownerHeader'];
        $ownerId = $collectionOwner['userId'];

        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            'api/v2/users/' . $ownerId . '/collections',
            $mockData,
            $ownerHeader
        );

        $response->assertStatus(201);
        $collectionId = $response->decodeResponseJson()['data'];

        $editData = [
            "name" => "edited name",
            "status" => "DRAFT"
        ];

        $response = $this->json(
            'PATCH',
            'api/v2/users/' . $ownerId . '/collections/' . $collectionId,
            $editData,
            $ownerHeader
        );

        $response->assertStatus(200);
        $content = $response->decodeResponseJson();

        $this->assertEquals('edited name', $content['data']['name']);
        $this->assertEquals('DRAFT', $content['data']['status']);

        // Test a different user cannot edit
        $otherUser = $this->createCollectionOwner();
        $otherUserHeader = $otherUser['ownerHeader'];

        $response = $this->json(
            'PATCH',
            'api/v2/users/' . $ownerId . '/collections/' . $collectionId,
            $editData,
            $otherUserHeader
        );
        $response->assertStatus(401);
    }

    /**
     * Delete users Collection with success
     *
     * @return void
     */
    public function test_delete_users_collection_with_success(): void
    {
        $collectionOwner = $this->createCollectionOwner();
        $ownerHeader = $collectionOwner['ownerHeader'];
        $ownerId = $collectionOwner['userId'];

        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            'api/v2/users/' . $ownerId . '/collections',
            $mockData,
            $ownerHeader
        );

        $response->assertStatus(201);
        $collectionId = $response->decodeResponseJson()['data'];

        $response = $this->json(
            'DELETE',
            'api/v2/users/' . $ownerId . '/collections/' . $collectionId,
            [],
            $ownerHeader
        );

        $response->assertStatus(200);
    }

    /**
     * Delete users Collection without success
     *
     * @return void
     */
    public function test_delete_users_collection_without_success(): void
    {
        $collectionOwner = $this->createCollectionOwner();
        $ownerHeader = $collectionOwner['ownerHeader'];
        $ownerId = $collectionOwner['userId'];

        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            'api/v2/users/' . $ownerId . '/collections',
            $mockData,
            $ownerHeader
        );

        $response->assertStatus(201);
        $collectionId = $response->decodeResponseJson()['data'];

        $otherUser = $this->createCollectionOwner();
        $otherUserHeader = $otherUser['ownerHeader'];

        $response = $this->json(
            'DELETE',
            'api/v2/users/' . $ownerId . '/collections/' . $collectionId,
            [],
            $otherUserHeader
        );
        $response->assertStatus(401);
    }


    // teams collections - GET, GET, GET (3 statuses), POST PUT PATVH

    /**
     * Create new teams Collection with success
     *
     * @return void
     */
    public function test_add_new_teams_collection_with_success(): void
    {
        $datasets = $this->generateDatasets();
        $nActive = Dataset::whereIn("id", array_column($datasets, 'id'))
            ->where('status', Dataset::STATUS_ACTIVE)
            ->count();

        $countBefore = Collection::count();

        $collectionTeam = $this->createCollectionTeam();
        $memberHeader = $collectionTeam['memberHeader'];
        $teamId = $collectionTeam['teamId'];

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
            'api/v2/teams/' . $teamId . '/collections',
            $mockData,
            $memberHeader
        );

        $countAfter = Collection::count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);

    }

    /**
     * Get teams Collection with success
     *
     * @return void
     */
    public function test_get_teams_collection_with_success(): void
    {
        $countBefore = Collection::count();

        $collectionTeam = $this->createCollectionTeam();
        $memberHeader = $collectionTeam['memberHeader'];
        $teamId = $collectionTeam['teamId'];

        // Create a collection with each status
        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            'api/v2/teams/' . $teamId . '/collections',
            $mockData,
            $memberHeader
        );

        $mockData = [
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
            "status" => "DRAFT"
        ];

        $response = $this->json(
            'POST',
            'api/v2/teams/' . $teamId . '/collections',
            $mockData,
            $memberHeader
        );

        $mockData = [
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
            "status" => "ARCHIVED"
        ];

        $response = $this->json(
            'POST',
            'api/v2/teams/' . $teamId . '/collections',
            $mockData,
            $memberHeader
        );

        // Test get active collections
        $response = $this->json(
            'GET',
            'api/v2/teams/' . $teamId . '/collections/status/active',
            [],
            $memberHeader
        );
        $response->assertStatus(200);
        $content = $response->decodeResponseJson();
        $this->assertEquals('ACTIVE', $content['data'][0]['status']);

        // Test get draft collections
        $response = $this->json(
            'GET',
            'api/v2/teams/' . $teamId . '/collections/status/draft',
            [],
            $memberHeader
        );
        $response->assertStatus(200);
        $content = $response->decodeResponseJson();
        $this->assertEquals('DRAFT', $content['data'][0]['status']);

        // Test get active collections
        $response = $this->json(
            'GET',
            'api/v2/teams/' . $teamId . '/collections/status/archived',
            [],
            $memberHeader
        );
        $response->assertStatus(200);
        $content = $response->decodeResponseJson();
        $this->assertEquals('ARCHIVED', $content['data'][0]['status']);

    }

    /**
     * Get teams Collection by id with success
     *
     * @return void
     */
    public function test_get_teams_collection_by_id_with_success(): void
    {
        $countBefore = Collection::count();

        $collectionTeam = $this->createCollectionTeam();
        $memberHeader = $collectionTeam['memberHeader'];
        $teamId = $collectionTeam['teamId'];

        // Create a collection with each status
        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            'api/v2/teams/' . $teamId . '/collections',
            $mockData,
            $memberHeader
        );
        $collectionId = $response->decodeResponseJson()['data'];

        // Test get active collections
        $response = $this->json(
            'GET',
            'api/v2/teams/' . $teamId . '/collections/' . $collectionId,
            [],
            $memberHeader
        );
        $response->assertJsonStructure([
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
                'users',
                'team',
            ],
        ]);
        $response->assertStatus(200);

        // Try to get collection through incorrect team
        $response = $this->json(
            'GET',
            'api/v2/teams/' . $teamId + 1 . '/collections/' . $collectionId,
            [],
            $memberHeader
        );
        $response->assertStatus(401);
    }

    /**
     * Update teams Collection with success
     *
     * @return void
     */
    public function test_update_teams_collection_with_success(): void
    {
        $collectionTeam = $this->createCollectionTeam();
        $memberHeader = $collectionTeam['memberHeader'];
        $teamId = $collectionTeam['teamId'];

        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            'api/v2/teams/' . $teamId . '/collections',
            $mockData,
            $memberHeader
        );

        $response->assertStatus(201);
        $collectionId = $response->decodeResponseJson()['data'];

        $updateData = [
            "name" => "updated name",
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
            "status" => "DRAFT"
        ];

        $response = $this->json(
            'PUT',
            'api/v2/teams/' . $teamId . '/collections/' . $collectionId,
            $updateData,
            $memberHeader
        );

        $response->assertStatus(200);
        $content = $response->decodeResponseJson();

        $this->assertEquals('updated name', $content['data']['name']);
        $this->assertEquals('DRAFT', $content['data']['status']);

        // Test a different team cannot update
        $otherTeam = $this->createCollectionTeam();
        $otherTeamHeader = $otherTeam['memberHeader'];

        $response = $this->json(
            'PUT',
            'api/v2/teams/' . $teamId . '/collections/' . $collectionId,
            $updateData,
            $otherTeamHeader
        );
        $response->assertStatus(401);
    }

    /**
     * Edit teams Collection with success
     *
     * @return void
     */
    public function test_edit_teams_collection_with_success(): void
    {
        $collectionTeam = $this->createCollectionTeam();
        $memberHeader = $collectionTeam['memberHeader'];
        $teamId = $collectionTeam['teamId'];

        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            'api/v2/teams/' . $teamId . '/collections',
            $mockData,
            $memberHeader
        );

        $response->assertStatus(201);
        $collectionId = $response->decodeResponseJson()['data'];

        $editData = [
            "name" => "edited name",
            "status" => "DRAFT"
        ];

        $response = $this->json(
            'PATCH',
            'api/v2/teams/' . $teamId . '/collections/' . $collectionId,
            $editData,
            $memberHeader
        );

        $response->assertStatus(200);
        $content = $response->decodeResponseJson();

        $this->assertEquals('edited name', $content['data']['name']);
        $this->assertEquals('DRAFT', $content['data']['status']);

        // Test a different team cannot edit
        $otherTeam = $this->createCollectionTeam();
        $otherTeamHeader = $otherTeam['memberHeader'];

        $response = $this->json(
            'PATCH',
            'api/v2/teams/' . $teamId . '/collections/' . $collectionId,
            $editData,
            $otherTeamHeader
        );
        $response->assertStatus(401);
    }

    /**
     * Delete teams Collection with success
     *
     * @return void
     */
    public function test_delete_teams_collection_with_success(): void
    {
        $collectionTeam = $this->createCollectionTeam();
        $memberHeader = $collectionTeam['memberHeader'];
        $teamId = $collectionTeam['teamId'];

        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            'api/v2/teams/' . $teamId . '/collections',
            $mockData,
            $memberHeader
        );

        $response->assertStatus(201);
        $collectionId = $response->decodeResponseJson()['data'];

        $response = $this->json(
            'DELETE',
            'api/v2/teams/' . $teamId . '/collections/' . $collectionId,
            [],
            $memberHeader
        );

        $response->assertStatus(200);
    }

    /**
     * Delete teams Collection without success
     *
     * @return void
     */
    public function test_delete_teams_collection_without_success(): void
    {
        $collectionTeam = $this->createCollectionTeam();
        $memberHeader = $collectionTeam['memberHeader'];
        $teamId = $collectionTeam['teamId'];

        $mockData = [
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
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            'api/v2/teams/' . $teamId . '/collections',
            $mockData,
            $memberHeader
        );

        $response->assertStatus(201);
        $collectionId = $response->decodeResponseJson()['data'];

        $otherTeam = $this->createCollectionTeam();
        $otherTeamHeader = $otherTeam['memberHeader'];

        $response = $this->json(
            'DELETE',
            'api/v2/teams/' . $teamId . '/collections/' . $collectionId,
            [],
            $otherTeamHeader
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

    private function createCollectionOwner()
    {
        $email = fake()->regexify('[A-Z]{5}[0-4]{1}') . '@test.com';
        // create a user to own this collection
        $responseUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'XXXXXXXXXX',
                'lastname' => 'XXXXXXXXXX',
                'email' => $email,
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'contact_feedback' => 1,
                'contact_news' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/12345678",
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header
        );
        $responseUser->assertStatus(201);
        $uniqueUserId = $responseUser->decodeResponseJson()['data'];
        $response = $this->json(
            'POST',
            '/api/v1/auth',
            [
                'email' => $email,
                'password' => 'Passw@rd1!',
            ],
            ['Accept' => 'application/json']
        );
        $jwt = $response['access_token'];
        $ownerHeader = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];

        return [
            'ownerHeader' => $ownerHeader,
            'userId' => $uniqueUserId
        ];
    }

    private function createCollectionTeam()
    {
        $email = fake()->regexify('[A-Z]{5}[0-4]{1}') . '@test.com';
        // create a user to be in the team
        $responseUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'XXXXXXXXXX',
                'lastname' => 'XXXXXXXXXX',
                'email' => $email,
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'contact_feedback' => 1,
                'contact_news' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/12345678",
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header
        );
        $responseUser->assertStatus(201);
        $uniqueUserId = $responseUser->decodeResponseJson()['data'];
        $response = $this->json(
            'POST',
            '/api/v1/auth',
            [
                'email' => $email,
                'password' => 'Passw@rd1!',
            ],
            ['Accept' => 'application/json']
        );
        $jwt = $response['access_token'];
        $userHeader = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];

        // Create team for the user to belong to
        $responseTeam = $this->json(
            'POST',
            'api/v1/teams',
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => TeamMemberOf::HUB,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'is_question_bank' => 1,
                'users' => [$uniqueUserId],
                'notifications' => [],
                'url' => 'https://fakeimg.pl/350x200/ff0000/000',
                'introduction' => fake()->sentence(),
                'dar_modal_content' => fake()->sentence(),
                'service' => 'https://service.local/test',
            ],
            $this->header
        );
        $responseTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $content = $responseTeam->decodeResponseJson();
        $teamId = $content['data'];

        return [
            'memberHeader' => $userHeader,
            'teamId' => $teamId
        ];
    }
}
