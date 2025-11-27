<?php

namespace Tests\Feature;

use Config;
use App\Models\Dur;
use Tests\TestCase;
use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Collection;
use App\Models\CollectionHasUser;
use App\Models\Publication;
use App\Models\TeamHasUser;
use Tests\Traits\MockExternalApis;

class CollectionTeamTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private function testUrl(int $teamId)
    {
        return '/api/v1/teams/' . $teamId . '/collections/';
    }
    public const TEST_URL = '/api/v1/collections';

    protected $header = [];
    protected $nonAdminJwt = '';
    protected $headerNonAdmin = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

        $this->authorisationUser(false);
        $this->nonAdminJwt = $this->getAuthorisationJwt(false);
        $this->headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->nonAdminJwt,
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
    public function test_add_new_active_team_collection_with_success(): void
    {
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
            "status" => "ACTIVE",
        ];

        // use a non-admin user, and assign them to a team as custodian.team.admin
        [
            'nonAdminUser' => $nonAdminUser,
            'team' => $team
        ] = $this->getNonAdminUserAsCustodianTeamAdminInTeam();

        // create a Collection as that user in the selected team
        $response = $this->json(
            'POST',
            $this->testUrl($team->id),
            $mockData,
            $this->headerNonAdmin
        );

        $countAfter = Collection::count();
        $countNewRow = $countAfter - $countBefore;

        $response->assertStatus(201);
        $this->assertTrue((bool) $countNewRow, 'Response was successful');

        $collectionId = $response['data'];
        $collection = Collection::where('id', $collectionId)->first();
        $this->assertEquals($collection->team_id, $team->id);

        $collectionHasUsers = CollectionHasUser::where('collection_id', $collectionId)->get();
        $this->assertEquals(1, count($collectionHasUsers));
        $this->assertEquals("CREATOR", $collectionHasUsers[0]->role);
        $this->assertEquals($nonAdminUser['id'], $collectionHasUsers[0]->user_id);
    }

    public function test_add_new_draft_team_collection_with_success(): void
    {
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
            "status" => "DRAFT"
        ];

        // use a non-admin user, and assign them to a team as custodian.team.admin
        [
            'nonAdminUser' => $nonAdminUser,
            'team' => $team
        ] = $this->getNonAdminUserAsCustodianTeamAdminInTeam();

        // create a Collection as that user in the selected team
        $response = $this->json(
            'POST',
            $this->testUrl($team->id),
            $mockData,
            $this->headerNonAdmin
        );

        $countAfter = Collection::count();
        $countNewRow = $countAfter - $countBefore;
        $response->assertStatus(201);
        $this->assertTrue((bool) $countNewRow, 'Response was successful');

        $collectionId = $response['data'];
        $collection = Collection::where('id', $collectionId)->first();
        $this->assertEquals($collection->team_id, $team->id);

        $collectionHasUsers = CollectionHasUser::where('collection_id', $collectionId)->get();
        $this->assertEquals(1, count($collectionHasUsers));
        $this->assertEquals("CREATOR", $collectionHasUsers[0]->role);
        $this->assertEquals($nonAdminUser['id'], $collectionHasUsers[0]->user_id);
    }

    /**
     * Update Collection with success by id
     *
     * @return void
     */
    public function test_update_team_collection_with_success(): void
    {
        $datasets = $this->generateDatasets();

        // use a non-admin user, and assign them to a team as custodian.team.admin
        [
            'nonAdminUser' => $nonAdminUser,
            'team' => $team
        ] = $this->getNonAdminUserAsCustodianTeamAdminInTeam();

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
        ];

        $response = $this->json(
            'POST',
            $this->testUrl($team->id),
            $mockDataIn,
            $this->headerNonAdmin
        );
        $response->assertStatus(201);
        $idIn = (int) $response['data'];

        $collectionHasUsers = CollectionHasUser::where(['collection_id' => $idIn])->count();
        // one creator (jwt)
        $this->assertTrue((int)$collectionHasUsers === 1);

        // update collection
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
        ];
        $responseUpdate = $this->json(
            'PUT',
            $this->testUrl($team->id) . $idIn,
            $mockDataUpdate,
            $this->headerNonAdmin
        );

        $responseUpdate->assertStatus(200);
        $this->assertTrue($mockDataUpdate['name'] === $responseUpdate['data']['name']);
        $this->assertTrue($mockDataUpdate['description'] === $responseUpdate['data']['description']);
        $this->assertTrue((bool) $mockDataUpdate['enabled'] === (bool) $responseUpdate['data']['enabled']);
        $this->assertTrue((bool) $mockDataUpdate['public'] === (bool) $responseUpdate['data']['public']);
        $this->assertTrue((int) $mockDataUpdate['counter'] === (int) $responseUpdate['data']['counter']);

        // generate jwt for a different user than the one who created the collection, and give them custodian.team.admin role on that team
        $this->authorisationUser(false, 2);

        $nonAdmin2Jwt = $this->getAuthorisationJwt(false, 2);
        [
            'nonAdminUser' => $nonAdminUser2,
            'team' => $team
        ] = $this->getNonAdminUserAsCustodianTeamAdminInTeam($nonAdmin2Jwt, $team);
        $headerNonAdmin2 = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdmin2Jwt,
        ];

        $responseUpdate = $this->json(
            'PUT',
            $this->testUrl($team->id) . $idIn,
            $mockDataUpdate,
            $headerNonAdmin2
        );

        $responseUpdate->assertStatus(200);
    }

    /**
     * Update Collection without success by id
     *
     * @return void
     */
    public function test_update_team_collection_without_success(): void
    {
        $datasets = $this->generateDatasets();

        // create new collection
        $mockDataIns = [
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
        ];

        // use a non-admin user, and assign them to a team as custodian.team.admin
        [
            'nonAdminUser' => $nonAdminUser,
            'team' => $team
        ] = $this->getNonAdminUserAsCustodianTeamAdminInTeam();

        $responseIn = $this->json(
            'POST',
            $this->testUrl($team->id),
            $mockDataIns,
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
            "datasets" => $datasets,
            "tools" => $this->generateTools(),
            "keywords" => $this->generateKeywords(),
            "dur" => $this->generateDurs(),
            "publications" => $this->generatePublications(),
            "status" => "ACTIVE"
        ];

        // generate jwt for a different user than the one who created the collection
        $this->authorisationUser(false, 2);

        $nonAdmin2Jwt = $this->getAuthorisationJwt(false, 2);
        $headerNonAdmin2 = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdmin2Jwt,
        ];

        $nonAdminUser2 = $this->getUserFromJwt($nonAdmin2Jwt);

        // double-check they're not in the same team anyway - we want them to have no access
        $teamHasUser = TeamHasUser::where(['user_id' => $nonAdminUser2['id'], 'team_id' => $team->id])->first();
        if ($teamHasUser) {
            $teamHasUser->delete();
        }

        $responseUpdate = $this->json(
            'PUT',
            $this->testUrl($team->id) . $idIn,
            $mockDataUpdate,
            $headerNonAdmin2
        );

        $responseUpdate->assertStatus(401); // Unauthorized
    }

    /**
     * Update Collection with success by id
     *
     * @return void
     */
    public function test_update_team_collection_to_draft_with_success(): void
    {
        // use a non-admin user, and assign them to a team as custodian.team.admin
        [
            'nonAdminUser' => $nonAdminUser,
            'team' => $team
        ] = $this->getNonAdminUserAsCustodianTeamAdminInTeam();

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
            $this->testUrl($team->id),
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
            "publications" => $this->generatePublications(),
            "status" => "DRAFT"
        ];
        $responseUpdate = $this->json(
            'PUT',
            $this->testUrl($team->id) . $idIn,
            $mockDataUpdate,
            $this->headerNonAdmin
        );
        $responseUpdate->assertStatus(200);
    }

    /**
     * Edit Collection with success by id
     *
     * @return void
     */
    public function test_edit_team_collection_with_success(): void
    {
        // use a non-admin user, and assign them to a team as custodian.team.admin
        [
            'nonAdminUser' => $nonAdminUser,
            'team' => $team
        ] = $this->getNonAdminUserAsCustodianTeamAdminInTeam();

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
        ];
        $responseIns = $this->json(
            'POST',
            $this->testUrl($team->id),
            $mockDataIns,
            $this->headerNonAdmin
        );
        $responseIns->assertStatus(201);
        $idIns = (int) $responseIns['data'];

        // update collection with same user
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
            $this->testUrl($team->id) . $idIns,
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
            $this->testUrl($team->id) . $idIns,
            $mockDataEdit1,
            $this->headerNonAdmin
        );
        $responseEdit1->assertStatus(200);
        $this->assertTrue($mockDataEdit1['name'] === $responseEdit1['data']['name']);
        $this->assertTrue($mockDataEdit1['description'] === $responseEdit1['data']['description']);
        $this->assertTrue($mockDataEdit1['image_link'] === $responseEdit1['data']['image_link']);

        // edit with another user
        $mockDataEdit2 = [
            "name" => "covid another edit",
            "counter" => 126
        ];

        // generate jwt for a different user than the one who created the collection, and give them custodian.team.admin role on that team
        $this->authorisationUser(false, 2);

        $nonAdmin2Jwt = $this->getAuthorisationJwt(false, 2);
        [
            'nonAdminUser' => $nonAdminUser2,
            'team' => $team
        ] = $this->getNonAdminUserAsCustodianTeamAdminInTeam($nonAdmin2Jwt, $team);
        $headerNonAdmin2 = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdmin2Jwt,
        ];

        $responseEdit2 = $this->json(
            'PATCH',
            $this->testUrl($team->id) . $idIns,
            $mockDataEdit2,
            $headerNonAdmin2
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
    public function test_edit_team_collection_without_success(): void
    {
        // use a non-admin user, and assign them to a team as custodian.team.admin
        [
            'nonAdminUser' => $nonAdminUser,
            'team' => $team
        ] = $this->getNonAdminUserAsCustodianTeamAdminInTeam();

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
        ];
        $responseIns = $this->json(
            'POST',
            $this->testUrl($team->id),
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
            "status" => "DRAFT",
        ];
        $responseUpdate = $this->json(
            'PUT',
            $this->testUrl($team->id) . $idIns,
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

        // generate jwt for a different user than the one who created the collection
        $this->authorisationUser(false, 2);

        $nonAdmin2Jwt = $this->getAuthorisationJwt(false, 2);
        $headerNonAdmin2 = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdmin2Jwt,
        ];

        $nonAdminUser2 = $this->getUserFromJwt($nonAdmin2Jwt);

        // double-check they're not in the same team anyway - we want them to have no access
        $teamHasUser = TeamHasUser::where(['user_id' => $nonAdminUser2['id'], 'team_id' => $team->id])->first();
        if ($teamHasUser) {
            $teamHasUser->delete();
        }

        $responseEdit1 = $this->json(
            'PATCH',
            $this->testUrl($team->id) . $idIns,
            $mockDataEdit1,
            $headerNonAdmin2
        );

        $responseEdit1->assertStatus(401); // Unauthorized

    }

    /**
     * SoftDelete Collection by Id with success
     *
     * @return void
     */
    public function test_soft_delete_and_unarchive_team_collection_with_and_without_success(): void
    {
        $countBefore = Collection::count();
        $countTrashedBefore = Collection::onlyTrashed()->count();

        // use a non-admin user, and assign them to a team as custodian.team.admin
        [
            'nonAdminUser' => $nonAdminUser,
            'team' => $team
        ] = $this->getNonAdminUserAsCustodianTeamAdminInTeam();

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
            "status" => "ACTIVE"
        ];
        $responseIn = $this->json(
            'POST',
            $this->testUrl($team->id),
            $mockDataIn,
            $this->headerNonAdmin
        );
        $responseIn->assertStatus(201);
        $idIn = (int) $responseIn['data'];

        $countAfter = Collection::count();
        $this->assertEquals($countAfter, $countBefore + 1);

        // generate jwt for a different user than the one who created the collection
        $this->authorisationUser(false, 2);

        $nonAdmin2Jwt = $this->getAuthorisationJwt(false, 2);
        $headerNonAdmin2 = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdmin2Jwt,
        ];

        $nonAdminUser2 = $this->getUserFromJwt($nonAdmin2Jwt);

        // double-check they're not in the same team anyway - we want them to have no access
        $teamHasUser = TeamHasUser::where(['user_id' => $nonAdminUser2['id'], 'team_id' => $team->id])->first();
        if ($teamHasUser) {
            $teamHasUser->delete();
        }

        // try to delete collection when not in the correct team - this will fail
        $response = $this->json('DELETE', $this->testUrl($team->id) . $idIn, [], $headerNonAdmin2);
        $response->assertStatus(401);

        $countAfter = Collection::count();
        $countTrashedAfter = Collection::onlyTrashed()->count();

        $this->assertEquals($countAfter, $countBefore + 1);
        $this->assertEquals($countTrashedAfter, 0);


        // delete collection when authorised
        $response = $this->json('DELETE', $this->testUrl($team->id) . $idIn, [], $this->headerNonAdmin);
        $response->assertStatus(200);

        $countAfter = Collection::count();
        $countTrashedAfter = Collection::onlyTrashed()->count();

        $this->assertEquals($countAfter, $countBefore);
        $this->assertEquals($countTrashedAfter, 1);

        $response = $this->json(
            'PATCH',
            $this->testUrl($team->id) . $idIn . '?unarchive',
            ['status' => 'ACTIVE'],
            $this->headerNonAdmin
        );

        $response->assertStatus(200);

        $countTrashedAfterUnarchiving = Collection::onlyTrashed()->count();
        $countAfterUnarchiving = Collection::count();

        $this->assertEquals($countTrashedAfterUnarchiving, 0);
        $this->assertTrue($countAfter < $countAfterUnarchiving);
        $this->assertEquals($countBefore + 1, $countAfterUnarchiving);
    }


    /**
     * SoftDelete Collection by Id with success
     *
     * @return void
     */
    public function test_does_not_delete_index_on_draft_archived_team_collection(): void
    {
        // use a non-admin user, and assign them to a team as custodian.team.admin
        [
            'nonAdminUser' => $nonAdminUser,
            'team' => $team
        ] = $this->getNonAdminUserAsCustodianTeamAdminInTeam();

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
            "status" => "DRAFT"
        ];
        $responseIn = $this->json(
            'POST',
            $this->testUrl($team->id),
            $mockDataIn,
            $this->headerNonAdmin
        );
        $responseIn->assertStatus(201);
        $idIn = (int) $responseIn['data'];

        $response = $this->json('DELETE', $this->testUrl($team->id) . $idIn, [], $this->headerNonAdmin);
        $response->assertStatus(200);
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

    private function getNonAdminUserAsCustodianTeamAdminInTeam(mixed $jwt = null, ?Team $team = null)
    {
        if (!$jwt) {
            $jwt = $this->nonAdminJwt;
        }

        // get an existing non-admin user
        $nonAdminUser = $this->getUserFromJwt($jwt);
        // add them to a Team
        if (!$team) {
            $team = Team::all()->random();
        }

        TeamHasUser::create([
            'team_id' => (int)$team->id,
            'user_id' => (int)$nonAdminUser['id'],
        ]);
        // make them a custodian.team.admin so they have collections.create permissions
        $urlPost = 'api/v1/teams/' . $team->id . '/users';
        $arrayPermissions = ["custodian.team.admin"];
        $payload = [
            "userId" => $nonAdminUser['id'],
            "roles" => $arrayPermissions,
        ];
        $firstResponsePost = $this->json('POST', $urlPost, $payload, $this->header);

        // check response to making them a custodian.team.admin
        $firstResponsePost->assertJsonStructure([
            'message'
        ]);
        $firstResponsePost->assertStatus(201);

        return [
            "nonAdminUser" => $nonAdminUser,
            "team" => $team
        ];

    }
}
