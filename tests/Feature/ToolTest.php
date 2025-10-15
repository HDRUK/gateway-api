<?php

namespace Tests\Feature;

use Config;
use Exception;
use App\Models\Tag;
use Tests\TestCase;
use App\Models\Tool;
use App\Models\User;
use ReflectionClass;
use App\Models\Dataset;
use App\Models\License;
use App\Models\Collection;
use App\Models\DurHasTool;
use App\Models\ToolHasTag;
use App\Models\Publication;
use Illuminate\Support\Str;
use Tests\Traits\Authorization;
use App\Http\Enums\TeamMemberOf;
use App\Models\CollectionHasTool;
use App\Models\PublicationHasTool;
use Tests\Traits\MockExternalApis;
use App\Models\ToolHasTypeCategory;
use ElasticClientController as ECC;
use App\Models\DatasetVersionHasTool;
use App\Models\ToolHasProgrammingPackage;
use App\Models\ToolHasProgrammingLanguage;
use App\Http\Controllers\Api\V1\ToolController;
use App\Models\Category;
use App\Models\Dur;
use App\Models\ProgrammingLanguage;
use App\Models\ProgrammingPackage;
use App\Models\TypeCategory;

class ToolTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/tools';
    public const TEST_URL_TEAM = '/api/v1/teams';
    public const TEST_URL_NOTIFICATION = '/api/v1/notifications';
    public const TEST_URL_USER = '/api/v1/users';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * Get All Tools with success
     *
     * @return void
     */
    public function test_v1_get_all_tools_with_success(): void
    {
        $countTool = Tool::where('enabled', 1)->count();
        $response = $this->json('GET', self::TEST_URL, [], $this->header);
        $this->assertEquals($countTool, $response['total']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'mongo_object_id',
                    'name',
                    'url',
                    'description',
                    'results_insights',
                    'license',
                    'tech_stack',
                    'category_id',
                    'user_id',
                    'enabled',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'user',
                    'tag',
                    'associated_authors',
                    'contact_address',
                    'publications',
                    'durs',
                    'collections',
                    'datasets',
                    'any_dataset',
                    'type_category',
                    'category',
                ]
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
     * Get Tool by Id with success
     *
     * @return void
     */
    public function test_v1_get_tool_by_id_with_success(): void
    {
        $toolId = Tool::where('enabled', 1)->get()->random()->id;
        $response = $this->json('GET', self::TEST_URL . '/' . $toolId, [], $this->header);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'mongo_object_id',
                'name',
                'url',
                'description',
                'results_insights',
                'license',
                'tech_stack',
                'category_id',
                'user_id',
                'enabled',
                'created_at',
                'updated_at',
                'deleted_at',
                'user',
                'tag',
                'programming_languages',
                'programming_packages',
                'type_category',
                'category',
                'associated_authors',
                'contact_address',
                'publications',
                'durs',
                'collections',
                'datasets',
                'any_dataset',
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Create new Tool with success
     *
     * @return void
     */
    public function test_v1_add_new_tool_with_success(): void
    {
        ECC::shouldReceive("indexDocument")
            ->times(1);

        $licenseId = License::where('valid_until', null)->get()->random()->id ?? null;
        $this->assertNotNull($licenseId, 'No valid license ID found');

        $initialToolCount = Tool::withTrashed()->count();
        $initialTagCount = ToolHasTag::count();

        $mockData = [
            "mongo_object_id" => "5ece82082abda8b3a06f1941",
            "name" => "Similique sapiente est vero eum.",
            "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
            "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
            'results_insights' => "asfhiasfh aoshfa ",
            "license" => $licenseId,
            "tech_stack" => "Cumque molestias excepturi quam at.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => [1, 2],
            "dataset" => [1, 2],
            "programming_language" => [1, 2],
            "programming_package" => [1, 2],
            "type_category" => [1, 2],
            "enabled" => 1,
            "publications" => $this->generatePublications(),
            "durs" => [],
            "collections" => $this->generateCollections(),
            "any_dataset" => false,
            "status" => "ACTIVE"
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            $mockData,
            $this->header
        );

        $response->assertStatus(201);
        $toolId = $response['data'];

        $finalToolCount = Tool::withTrashed()->count();
        $finalTagCount = ToolHasTag::count();

        $newToolCount = $finalToolCount - $initialToolCount;
        $newTagCount = $finalTagCount - $initialTagCount;

        $this->assertTrue((bool)$newToolCount, 'New tool was not created');
        $this->assertEquals(2, $newTagCount, 'Number of new tags is not as expected');
        $count1 = Dataset::where('id', 1)->first()->versions()->count();
        $count2 = Dataset::where('id', 2)->first()->versions()->count();
        $finalDatasetVersions = DatasetVersionHasTool::where('tool_id', $toolId)->count();
        $this->assertEquals($finalDatasetVersions, $count1 + $count2);
    }


    /**
     * Get All tools for a given team with success
     *
     * @return void
     */
    public function test_v1_get_all_team_tools_with_success(): void
    {
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => null,
                'user_id' => 3,
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header,
        );

        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $teamName1 = 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}');

        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => $teamName1,
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => fake()->randomElement([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                    TeamMemberOf::NCS,
                ]),
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
                'users' => [],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreateTeam = $responseCreateTeam->decodeResponseJson();
        $teamId1 = $contentCreateTeam['data'];

        // Create a 2nd team
        $teamName2 = 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}');
        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => $teamName2,
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => fake()->randomElement([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                    TeamMemberOf::NCS,
                ]),
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
                'users' => [],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreateTeam = $responseCreateTeam->decodeResponseJson();
        $teamId2 = $contentCreateTeam['data'];

        // Create user
        $responseCreateUser = $this->json(
            'POST',
            self::TEST_URL_USER,
            [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'email' => 'firstname.lastname.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => " https://orcid.org/75697342",
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => 1234566,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header,
        );

        $responseCreateUser->assertStatus(201);
        $contentCreateUser = $responseCreateUser->decodeResponseJson();
        $userId = $contentCreateUser['data'];

        // Create Tool A
        $responseCreateTool = $this->json(
            'POST',
            self::TEST_URL,
            [
                'mongo_object_id' => '5ece82082abda8b3a06f1941',
                'name' => 'Tool A',
                'url' => 'http://example.com/toolA',
                'description' => 'Test Tool A Description',
                'results_insights' => 'mazing insights',
                'license' => 1,
                'tech_stack' => 'Tech Stack A',
                'category_id' => 1,
                'user_id' => $userId,
                'team_id' => $teamId1,
                'enabled' => 1,
                'tag' => [1, 2],
                'dataset' => [1, 2],
                'programming_language' => [1, 2],
                'programming_package' => [1, 2],
                'type_category' => [1, 2],
                'publications' => [],
                'durs' => [],
                'collections' => [],
                'any_dataset' => false,
            ],
            $this->header
        );
        $responseCreateTool->assertStatus(201);

        // Create Tool B
        $responseCreateTool = $this->json(
            'POST',
            self::TEST_URL,
            [
                'mongo_object_id' => '5ece82082abda8b3a06f1942',
                'name' => 'Tool B',
                'url' => 'http://example.com/toolB',
                'description' => 'Test Tool B Description',
                'results_insights' => 'other insights',
                'license' => 1,
                'tech_stack' => 'Tech Stack B',
                'category_id' => 1,
                'user_id' => $userId,
                'team_id' => $teamId1,
                'enabled' => 1,
                'tag' => [1, 2],
                'dataset' => [2],
                'programming_language' => [1, 2],
                'programming_package' => [1, 2],
                'type_category' => [1, 2],
                'publications' => [],
                'durs' => [],
                'collections' => $this->generateCollections(),
                'any_dataset' => false,
            ],
            $this->header
        );
        $responseCreateTool->assertStatus(201);

        // Create Tool C
        $responseCreateTool = $this->json(
            'POST',
            self::TEST_URL,
            [
                'mongo_object_id' => '5ece82082abda8b3a06f1943',
                'name' => 'Tool C',
                'url' => 'http://example.com/toolC',
                'description' => 'Test Tool C Description',
                'results_insights' => 'insights',
                'license' => 1,
                'tech_stack' => 'Tech Stack C',
                'category_id' => 1,
                'user_id' => $userId,
                'team_id' => $teamId2,
                'enabled' => 1,
                'tag' => [1, 2],
                'dataset' => [1],
                'programming_language' => [1, 2],
                'programming_package' => [1, 2],
                'type_category' => [1, 2],
                'publications' => [],
                'durs' => [1, 2],
                'collections' => [],
                'any_dataset' => false,
            ],
            $this->header
        );
        $responseCreateTool->assertStatus(201);

        // Use the explicit team_id, mongo_id, and name for filtering tests
        $toolA = Tool::where('name', 'Tool A')->first();
        $mongoId = $toolA->mongo_id;
        $teamId = $toolA->team_id;
        $title = $toolA->name;

        // Filter by mongo_id
        $response = $this->json('GET', self::TEST_URL . '?mongo_id=' . $mongoId, [], $this->header);
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData);
        foreach ($responseData as $tool) {
            $this->assertEquals($mongoId, $tool['mongo_id']);
        }

        // Filter by team_id
        $response = $this->json('GET', self::TEST_URL . '?team_id=' . $teamId, [], $this->header);
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData);
        foreach ($responseData as $tool) {
            $this->assertEquals($teamId, $tool['team_id']);
        }

        // Filter by user_id
        $response = $this->json('GET', self::TEST_URL . '?user_id=' . $userId, [], $this->header);
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData);
        foreach ($responseData as $tool) {
            $this->assertEquals($userId, $tool['user_id']);
        }

        // Filter by title
        $response = $this->json('GET', self::TEST_URL . '?title=' . urlencode($title), [], $this->header);
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData);
        foreach ($responseData as $tool) {
            $this->assertStringContainsString($title, $tool['name']);
        }

        // Test ascending order by title
        $response = $this->json('GET', self::TEST_URL . '?sort=name:asc', [], $this->header);
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $titles = array_column($responseData, 'name');
        $sortedTitles = $titles;
        sort($sortedTitles);
        $this->assertEquals($sortedTitles, $titles, "Ascending order sorting by title failed.");

        // Test descending order by title
        $response = $this->json('GET', self::TEST_URL . '?sort=name:desc', [], $this->header);
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $titles = array_column($responseData, 'name');
        $sortedTitles = $titles;
        rsort($sortedTitles);
        $this->assertEquals($sortedTitles, $titles, "Descending order sorting by title failed.");

        // Cleanup: Delete tools
        $toolIds = Tool::pluck('id')->toArray();
        foreach ($toolIds as $toolId) {
            $responseDeleteTool = $this->json(
                'DELETE',
                self::TEST_URL . '/' . $toolId . '?deletePermanently=true',
                [],
                $this->header
            );
            $responseDeleteTool->assertJsonStructure([
                'message'
            ]);
            $responseDeleteTool->assertStatus(200);
        }

        // Cleanup: Delete teams
        for ($i = 1; $i <= 2; $i++) {
            $responseDeleteTeam = $this->json(
                'DELETE',
                self::TEST_URL_TEAM . '/' . ${'teamId' . $i} . '?deletePermanently=true',
                [],
                $this->header
            );

            $responseDeleteTeam->assertJsonStructure([
                'message'
            ]);
            $responseDeleteTeam->assertStatus(200);
        }

        // Cleanup: Delete user
        $responseDeleteUser = $this->json(
            'DELETE',
            self::TEST_URL_USER . '/' . $userId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteUser->assertJsonStructure([
            'message'
        ]);
        $responseDeleteUser->assertStatus(200);
    }

    /**
     * Insert data into tool_has_tags table with success
     *
     * @return void
     */
    public function test_v1_insert_data_in_tool_has_tags(): void
    {
        ToolHasTag::truncate();

        $mockData = array(1);
        $mockToolId = 1;

        $toolController = new ToolController();
        $classReflection = new ReflectionClass($toolController);
        $insertToolHasTag = $classReflection->getMethod('insertToolHasTag');

        $insertToolHasTag->setAccessible(true);

        $response = $insertToolHasTag->invokeArgs($toolController, [$mockData, $mockToolId]);

        $countAfter = ToolHasTag::where('tool_id', $mockToolId)->count();

        $this->assertEquals(
            count($mockData),
            $countAfter,
            "actual value is equal to expected"
        );

        $this->assertTrue(true);
    }

    /**
     * Update Tool with sucess by id
     *
     * @return void
     */
    public function test_v1_update_tool_with_success(): void
    {

        ECC::shouldReceive("indexDocument")
            ->times(1);

        ECC::shouldReceive("deleteDocument")
            ->times(1);


        $licenseId = License::where('valid_until', null)->get()->random()->id;
        // insert
        $mockDataIns = array(
            "mongo_object_id" => "5ece82082abda8b3a06f1941",
            "name" => "Similique sapiente est vero eum.",
            "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
            "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
            'results_insights' => 'insights',
            "license" => $licenseId,
            "tech_stack" => "Cumque molestias excepturi quam at.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => array(1),
            "programming_language" => array(1, 2),
            "programming_package" => array(1, 2),
            "type_category" => array(1, 2),
            "enabled" => 1,
            "publications" => $this->generatePublications(),
            "durs" => [],
            "collections" => $this->generateCollections(),
            "any_dataset" => false,
            "status" => "ACTIVE"
        );
        $responseIns = $this->json(
            'POST',
            self::TEST_URL . '/',
            $mockDataIns,
            $this->header
        );
        $responseIns->assertStatus(201);
        $responseIns->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseIns->assertJsonStructure([
            'message',
            'data'
        ]);
        $this->assertEquals(
            $responseIns['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );
        $toolIdInsert = $responseIns['data'];

        $responseIns->assertStatus(201);

        // update
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $mockDataUpdate = array(
            "mongo_object_id" => "5ece82082abda8b3a06f1941",
            "name" => "Ea fuga ab aperiam nihil quis.",
            "url" => "http://dach.com/odio-facilis-ex-culpa",
            "description" => "Ut voluptatem reprehenderit pariatur. Ut quod quae odio aut. Deserunt adipisci molestiae non expedita quia atque ut. Quis distinctio culpa perferendis neque.",
            'results_insights' => 'insights',
            "license" => $licenseId,
            "tech_stack" => "Dolor accusamus rerum numquam et.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => array(2),
            "dataset" => [
                [
                    'id' => 4,
                    'link_type' => 'Used on',
                ],
                [
                    'id' => 5,
                    'link_type' => 'Other',
                ],
            ],
            "programming_language" => array(1),
            "programming_package" => array(1),
            "type_category" => array(1),
            "enabled" => 1,
            "publications" => $generatedPublications,
            "durs" => [1, 2],
            "collections" => $generatedCollections,
            "any_dataset" => false,
            "status" => "DRAFT"
        );

        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $toolIdInsert,
            $mockDataUpdate,
            $this->header
        );

        $responseUpdate->assertStatus(200);
        $responseUpdate->assertJsonStructure([
            'message',
            'data',
        ]);

        $this->assertEquals($responseUpdate['data']['name'], $mockDataUpdate['name']);
        $this->assertEquals($responseUpdate['data']['url'], $mockDataUpdate['url']);
        $this->assertEquals($responseUpdate['data']['description'], $mockDataUpdate['description']);
        $this->assertEquals($responseUpdate['data']['results_insights'], $mockDataUpdate['results_insights']);
        $this->assertEquals($responseUpdate['data']['license']['id'], $mockDataUpdate['license']);
        $this->assertEquals($responseUpdate['data']['tech_stack'], $mockDataUpdate['tech_stack']);
        $this->assertEquals($responseUpdate['data']['category_id'], $mockDataUpdate['category_id']);
        $this->assertEquals($responseUpdate['data']['user_id'], $mockDataUpdate['user_id']);
        $this->assertEquals($responseUpdate['data']['enabled'], $mockDataUpdate['enabled']);

        $toolHasTags = ToolHasTag::where('tool_id', $toolIdInsert)->get();

        $this->assertEquals(count($toolHasTags), 1);

        $this->assertEquals($toolHasTags[0]['tag_id'], 2);

        $toolHasProgrammingLanguages = ToolHasProgrammingLanguage::where('tool_id', $toolIdInsert)->get();
        $this->assertEquals(count($toolHasProgrammingLanguages), 1);
        $this->assertEquals($toolHasProgrammingLanguages[0]['programming_language_id'], 1);

        $toolHasProgrammingPackages = ToolHasProgrammingPackage::where('tool_id', $toolIdInsert)->get();
        $this->assertEquals(count($toolHasProgrammingPackages), 1);
        $this->assertEquals($toolHasProgrammingPackages[0]['programming_package_id'], 1);

        $toolHasTypeCategories = ToolHasTypeCategory::where('tool_id', $toolIdInsert)->get();
        $this->assertEquals(count($toolHasTypeCategories), 1);
        $this->assertEquals($toolHasTypeCategories[0]['type_category_id'], 1);

        $publicationHasTool = PublicationHasTool::where('tool_id', $toolIdInsert)->get();
        $this->assertEquals(count($publicationHasTool), count($generatedPublications));

        $durHasTool = DurHasTool::where('tool_id', $toolIdInsert)->get();
        $this->assertEquals(count($durHasTool), 2);
        $this->assertEquals($durHasTool[0]['dur_id'], 1);
        $this->assertEquals($durHasTool[1]['dur_id'], 2);

        $collectionHasTool = CollectionHasTool::where('tool_id', $toolIdInsert)->get();
        $this->assertEquals(count($collectionHasTool), count($generatedCollections));

        $count1 = Dataset::where('id', 4)->first()->versions()->count();
        $count2 = Dataset::where('id', 5)->first()->versions()->count();
        $finalDatasetVersions = DatasetVersionHasTool::where('tool_id', $toolIdInsert)->count();
        $this->assertEquals($finalDatasetVersions, $count1 + $count2);
    }

    /**
     * Edit Tool with sucess by id
     *
     * @return void
     */
    public function test_v1_edit_tool_with_success(): void
    {
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        // insert
        $mockDataIns = array(
            "mongo_object_id" => "5ece82082abda8b3a06f1941",
            "name" => "Similique sapiente est vero eum.",
            "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
            "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
            "license" => $licenseId,
            "tech_stack" => "Cumque molestias excepturi quam at.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => array(),
            "programming_language" => array(1),
            "programming_package" => array(1),
            "type_category" => array(1),
            "enabled" => 1,
            "publications" => $this->generatePublications(),
            "durs" => [],
            "collections" => $this->generateCollections(),
            "any_dataset" => false,
            "dataset" => [
                [
                    'id' => 4,
                    'link_type' => 'Used on',
                ],
                [
                    'id' => 5,
                    'link_type' => 'Other',
                ],
            ],

        );
        $responseIns = $this->json(
            'POST',
            self::TEST_URL . '/',
            $mockDataIns,
            $this->header
        );
        $responseIns->assertStatus(201);
        $responseIns->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseIns->assertJsonStructure([
            'message',
            'data'
        ]);
        $this->assertEquals(
            $responseIns['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );
        $toolIdInsert = $responseIns['data'];
        $responseIns->assertStatus(201);

        // update
        $mockDataUpdate = array(
            "mongo_object_id" => "5ece82082abda8b3a06f1941",
            "name" => "Ea fuga ab aperiam nihil quis.",
            "url" => "http://dach.com/odio-facilis-ex-culpa",
            "description" => "Ut voluptatem reprehenderit pariatur. Ut quod quae odio aut. Deserunt adipisci molestiae non expedita quia atque ut. Quis distinctio culpa perferendis neque.",
            "license" => $licenseId,
            "tech_stack" => "Dolor accusamus rerum numquam et.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => array(2),
            "enabled" => 1,
            "publications" => $this->generatePublications(),
        );

        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $toolIdInsert,
            $mockDataUpdate,
            $this->header
        );

        $responseUpdate->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseUpdate->assertStatus(200);
        $this->assertEquals($responseUpdate['data']['name'], $mockDataUpdate['name']);
        $this->assertEquals($responseUpdate['data']['url'], $mockDataUpdate['url']);
        $this->assertEquals($responseUpdate['data']['description'], $mockDataUpdate['description']);
        $this->assertEquals($responseUpdate['data']['license']['id'], $mockDataUpdate['license']);
        $this->assertEquals($responseUpdate['data']['tech_stack'], $mockDataUpdate['tech_stack']);
        $this->assertEquals($responseUpdate['data']['category_id'], $mockDataUpdate['category_id']);
        $this->assertEquals($responseUpdate['data']['user_id'], $mockDataUpdate['user_id']);
        $this->assertEquals($responseUpdate['data']['enabled'], $mockDataUpdate['enabled']);

        $toolHasTags = ToolHasTag::where('tool_id', $toolIdInsert)->get();

        $this->assertEquals(count($toolHasTags), 1);

        $this->assertEquals($toolHasTags[0]['tag_id'], 2);

        // edit
        $mockDataEdit1 = array(
            "name" => "Ea fuga ab aperiam nihil quis e1.",
            "description" => "Ut voluptatem reprehenderit pariatur. Ut quod quae odio aut. Deserunt adipisci molestiae non expedita quia atque ut. Quis distinctio culpa perferendis neque. e1",
        );

        $responseEdit1 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $toolIdInsert,
            $mockDataEdit1,
            $this->header
        );

        $responseEdit1->assertJsonStructure([
            'message',
            'data',
        ]);
        $responseEdit1->assertStatus(200);
        $this->assertEquals($responseEdit1['data']['name'], $mockDataEdit1['name']);
        $this->assertEquals($responseEdit1['data']['description'], $mockDataEdit1['description']);

        // edit
        $licenseIdNew = License::where('valid_until', null)->get()->random()->id;
        $mockDataEdit2 = [
            'url' => 'http://dach.com/odio-facilis-ex-culpa-e2',
            'license' => $licenseIdNew,
            'tech_stack' => 'Dolor accusamus rerum numquam et. e2',
        ];
        $responseEdit2 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $toolIdInsert,
            $mockDataEdit2,
            $this->header
        );

        $responseEdit2->assertJsonStructure([
            'message',
            'data',
        ]);
        $responseEdit2->assertStatus(200);
        $this->assertEquals($responseEdit2['data']['url'], $mockDataEdit2['url']);
        $this->assertEquals($responseEdit2['data']['license']['id'], $mockDataEdit2['license']);
        $this->assertEquals($responseEdit2['data']['tech_stack'], $mockDataEdit2['tech_stack']);
    }

    /**
     * Create, delete, update, delete, edit, and delete a Tool with success
     *
     * @return void
     */
    public function test_v1_create_archive_unarchive_tool_with_success(): void
    {
        $licenseId = License::where('valid_until', null)->get()->random()->id;

        // Insert
        $mockDataIns = array(
            "mongo_object_id" => "5ece82082abda8b3a06f1941",
            "name" => "Similique sapiente est vero eum.",
            "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
            "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
            "license" => $licenseId,
            "tech_stack" => "Cumque molestias excepturi quam at.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => array(2),
            "programming_language" => array(1),
            "programming_package" => array(1),
            "type_category" => array(1),
            "enabled" => 1,
            "publications" => $this->generatePublications(),
            "any_dataset" => false,
        );

        $responseIns = $this->json(
            'POST',
            self::TEST_URL . '/',
            $mockDataIns,
            $this->header
        );

        $responseIns->assertStatus(201);
        $responseIns->assertJsonStructure([
            'message',
            'data',
        ]);

        $this->assertEquals(
            $responseIns['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );
        $toolIdInsert = $responseIns['data'];

        // Delete
        $responseDelete = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $toolIdInsert,
            [],
            $this->header
        );
        $responseDelete->assertStatus(200);

        // Unarchive tool
        $responseUnarchive = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $toolIdInsert . '?unarchive',
            ['status' => 'DRAFT'],
            $this->header
        );
        $responseUnarchive->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseUnarchive->assertStatus(200);

        // Verify that the unarchived tool has deleted_at == null
        $toolData = $responseUnarchive['data'];
        $this->assertNull($toolData['deleted_at']);


        // Delete again
        $responseDeleteAgain = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $toolIdInsert,
            [],
            $this->header
        );
        $responseDeleteAgain->assertStatus(200);
    }

    /**
     * Update Tool with success by id and generate an exception
     *
     * @return void
     */
    public function test_v1_update_tool_and_generate_exception(): void
    {
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $mockData = array(
            "mongo_object_id" => "5ece82082abda8b3a06f1941",
            "name" => "Similique sapiente est vero eum.",
            "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
            "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
            "license" => $licenseId,
            "tech_stack" => "Cumque molestias excepturi quam at.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => array(1, 2),
            "programming_language" => array(1),
            "programming_package" => array(1),
            "type_category" => array(1),
            "enabled" => 1,
            "publications" => $this->generatePublications(),
            "any_dataset" => false,
            "dataset" => [
                [
                    'id' => 4,
                    'link_type' => 'Used on',
                ],
                [
                    'id' => 5,
                    'link_type' => 'Other',
                ],
            ],
        );
        $id = 10000;

        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            $mockData,
            $this->header
        );
        $response->assertStatus(400);
    }

    /**
     * SoftDelete Tool by Id with success
     *
     * @return void
     */
    public function test_v1_soft_delete_tool_with_success(): void
    {
        $tools = Tool::first();
        $countBefore = Tool::onlyTrashed()->count();
        $response = $this->json('DELETE', self::TEST_URL . '/' . $tools->id, [], $this->header);
        $countAfter = Tool::onlyTrashed()->count();

        $response->assertStatus(200);

        $this->assertEquals(
            $countBefore + 1,
            $countAfter,
            "actual value is equals to expected"
        );
    }

    public function test_v1_create_tool_A_with_success()
    {
        // create User a.one@test.com
        $idUserAOne = $this->createTestUser('a.one@test.com');

        // create Team A
        $idTeamA = $this->createTestTeam();

        // assign User a.one@test.com to Team A
        $permissions = ["developer", "custodian.dar.manager"];
        $this->assignUserToTeam($idTeamA, $idUserAOne, $permissions);

        // generate jwt for user a.one@test.com
        $jwtUserAOne = $this->getTestUserAuthorization('a.one@test.com');

        // user a.one@test.com create Tool AOne with Team A
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $responseCreateTool = $this->json(
            'POST',
            '/api/v1/tools',
            [
                'mongo_object_id' => strtolower(Str::random(24)),
                'name' => 'Tool A',
                'url' => fake()->url(),
                'description' => 'Test Tool A Description',
                'results_insights' => 'mazing insights',
                'license' => $licenseId,
                'tech_stack' => fake()->words(3, true),
                'category_id' => $categoryId,
                'user_id' => $idUserAOne,
                'team_id' => $idTeamA,
                'enabled' => 1,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserAOne,
            ]
        );

        // success
        $responseCreateTool->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
    }

    public function test_v1_create_tool_A_with_no_success()
    {
        // create User a.one@test.com
        $idUserAOne = $this->createTestUser('a.one@test.com');

        // create Team A
        $idTeamA = $this->createTestTeam();

        // create Team B
        $idTeamB = $this->createTestTeam();

        // assign User a.one@test.com to Team A
        $permissions = ["developer", "custodian.dar.manager"];
        $this->assignUserToTeam($idTeamA, $idUserAOne, $permissions);

        // generate jwt for user a.one@test.com
        $jwtUserAOne = $this->getTestUserAuthorization('a.one@test.com');

        // user a.one@test.com create Tool AOne with Team A
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $responseCreateTool = $this->json(
            'POST',
            self::TEST_URL,
            [
                'mongo_object_id' => strtolower(Str::random(24)),
                'name' => 'Tool A',
                'url' => fake()->url(),
                'description' => fake()->sentence(),
                'results_insights' => fake()->sentence(),
                'license' => $licenseId,
                'tech_stack' => fake()->words(3, true),
                'category_id' => $categoryId,
                'user_id' => $idUserAOne,
                'team_id' => $idTeamB,
                'enabled' => 1,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserAOne,
            ]
        );

        // no success
        $responseCreateTool->assertStatus(401);
    }

    public function test_v1_edit_tool_A_same_user_with_success()
    {
        // create User a.one@test.com
        $idUserAOne = $this->createTestUser('a.one@test.com');

        // create Team A
        $idTeamA = $this->createTestTeam();

        // assign User a.one@test.com to Team A
        $permissions = ["developer", "custodian.dar.manager"];
        $this->assignUserToTeam($idTeamA, $idUserAOne, $permissions);

        // generate jwt for user a.one@test.com
        $jwtUserAOne = $this->getTestUserAuthorization('a.one@test.com');

        // user a.one@test.com create Tool AOne with Team A
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $responseCreateTool = $this->json(
            'POST',
            '/api/v1/tools',
            [
                'mongo_object_id' => strtolower(Str::random(24)),
                'name' => 'Tool A',
                'url' => fake()->url(),
                'description' => fake()->sentence(),
                'results_insights' => fake()->sentence(),
                'license' => $licenseId,
                'tech_stack' => fake()->words(3, true),
                'category_id' => $categoryId,
                'user_id' => $idUserAOne,
                'team_id' => $idTeamA,
                'enabled' => 1,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserAOne,
            ]
        );

        // success
        $responseCreateTool->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $toolId = $responseCreateTool['data'];

        // edit tool
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $responseEdit = $this->json(
            'PATCH',
            '/api/v1/tools/' . $toolId,
            [
                'mongo_object_id' => strtolower(Str::random(24)),
                'name' => 'Tool A',
                'url' => fake()->url(),
                'description' => fake()->sentence(),
                'results_insights' => fake()->sentence(),
                'license' => $licenseId,
                'tech_stack' => fake()->words(3, true),
                'category_id' => 1,
                'user_id' => 1,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'enabled' => 1,
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
                'status' => "DRAFT"
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserAOne,
            ]
        );

        // success
        $responseEdit->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }

    public function test_v1_edit_tool_A_by_another_user_from_same_team_with_no_success()
    {
        // create User a.one@test.com
        $idUserAOne = $this->createTestUser('a.one@test.com');

        // create Team A
        $idTeamA = $this->createTestTeam();

        // assign User a.one@test.com to Team A
        $permissions = ["developer", "custodian.dar.manager"];
        $this->assignUserToTeam($idTeamA, $idUserAOne, $permissions);

        // generate jwt for user a.one@test.com
        $jwtUserAOne = $this->getTestUserAuthorization('a.one@test.com');

        // user a.one@test.com create Tool AOne with Team A
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $responseCreateTool = $this->json(
            'POST',
            '/api/v1/tools',
            [
                'mongo_object_id' => strtolower(Str::random(24)),
                'name' => 'Tool A',
                'url' => fake()->url(),
                'description' => fake()->sentence(),
                'results_insights' => fake()->sentence(),
                'license' => $licenseId,
                'tech_stack' => fake()->words(3, true),
                'category_id' => $categoryId,
                'user_id' => $idUserAOne,
                'team_id' => $idTeamA,
                'enabled' => 1,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserAOne,
            ]
        );

        // success
        $responseCreateTool->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $toolId = $responseCreateTool['data'];

        // create User a.two@test.com
        $idUserATwo = $this->createTestUser('a.two@test.com');

        // assign User a.one@test.com to Team A
        $permissions = ["custodian.team.admin"];
        $this->assignUserToTeam($idTeamA, $idUserATwo, $permissions);

        // generate jwt for user a.one@test.com
        $jwtUserATwo = $this->getTestUserAuthorization('a.two@test.com');

        // edit tool
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();

        $responseEdit = $this->json(
            'PATCH',
            '/api/v1/tools/' . $toolId,
            [
                'mongo_object_id' => strtolower(Str::random(24)),
                'name' => 'Tool A',
                'url' => fake()->url(),
                'description' => fake()->sentence(),
                'results_insights' => fake()->sentence(),
                'license' => $licenseId,
                'tech_stack' => fake()->words(3, true),
                'category_id' => 1,
                'user_id' => 1,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'enabled' => 1,
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
                'status' => "DRAFT"
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserATwo,
            ]
        );

        // error
        $responseEdit->assertStatus(401);
    }

    public function test_v1_edit_tool_A_by_another_user_from_another_team_with_no_success()
    {
        // create User a.one@test.com
        $idUserAOne = $this->createTestUser('a.one@test.com');

        // create Team A
        $idTeamA = $this->createTestTeam();

        // assign User a.one@test.com to Team A
        $permissions = ["developer", "custodian.dar.manager"];
        $this->assignUserToTeam($idTeamA, $idUserAOne, $permissions);

        // generate jwt for user a.one@test.com
        $jwtUserAOne = $this->getTestUserAuthorization('a.one@test.com');

        // user a.one@test.com create Tool AOne with Team A
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $responseCreateTool = $this->json(
            'POST',
            '/api/v1/tools',
            [
                'mongo_object_id' => strtolower(Str::random(24)),
                'name' => 'Tool A',
                'url' => fake()->url(),
                'description' => fake()->sentence(),
                'results_insights' => fake()->sentence(),
                'license' => $licenseId,
                'tech_stack' => fake()->words(3, true),
                'category_id' => $categoryId,
                'user_id' => $idUserAOne,
                'team_id' => $idTeamA,
                'enabled' => 1,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserAOne,
            ]
        );

        // success
        $responseCreateTool->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $toolId = $responseCreateTool['data'];

        // create User b.one@test.com
        $idUserBOne = $this->createTestUser('b.one@test.com');

        // create Team A
        $idTeamB = $this->createTestTeam();

        // assign User b.one@test.com to Team B
        $permissions = ["custodian.team.admin"];
        $this->assignUserToTeam($idTeamB, $idUserBOne, $permissions);

        // generate jwt for user b.one@test.com
        $jwtUserBOne = $this->getTestUserAuthorization('b.one@test.com');

        // edit tool
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();

        $responseEdit = $this->json(
            'PATCH',
            '/api/v1/tools/' . $toolId,
            [
                'mongo_object_id' => strtolower(Str::random(24)),
                'name' => 'Tool A',
                'url' => fake()->url(),
                'description' => fake()->sentence(),
                'results_insights' => fake()->sentence(),
                'license' => $licenseId,
                'tech_stack' => fake()->words(3, true),
                'category_id' => 1,
                'user_id' => 1,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'enabled' => 1,
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
                'status' => "DRAFT"
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserBOne,
            ]
        );

        // error
        $responseEdit->assertStatus(401);
    }

    public function test_v1_update_tool_A_by_same_user_with_success()
    {
        // create User a.one@test.com
        $idUserAOne = $this->createTestUser('a.one@test.com');

        // create Team A
        $idTeamA = $this->createTestTeam();

        // assign User a.one@test.com to Team A
        $permissions = ["developer", "custodian.dar.manager"];
        $this->assignUserToTeam($idTeamA, $idUserAOne, $permissions);

        // generate jwt for user a.one@test.com
        $jwtUserAOne = $this->getTestUserAuthorization('a.one@test.com');

        // user a.one@test.com create Tool AOne with Team A
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $responseCreateTool = $this->json(
            'POST',
            '/api/v1/tools',
            [
                'mongo_object_id' => strtolower(Str::random(24)),
                'name' => 'Tool A',
                'url' => fake()->url(),
                'description' => fake()->sentence(),
                'results_insights' => fake()->sentence(),
                'license' => $licenseId,
                'tech_stack' => fake()->words(3, true),
                'category_id' => $categoryId,
                'user_id' => $idUserAOne,
                'team_id' => $idTeamA,
                'enabled' => 1,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserAOne,
            ]
        );

        // success
        $responseCreateTool->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $toolId = $responseCreateTool['data'];

        // update tool
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $responseUpdate = $this->json(
            'PUT',
            '/api/v1/tools/' . $toolId,
            [
                'name' => 'Tool A',
                'user_id' => $idUserAOne,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'enabled' => 1,
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
                'status' => 'ACTIVE',
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserAOne,
            ]
        );

        // success
        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }

    public function test_v1_update_tool_A_by_another_user_from_same_team_with_no_success()
    {
        // create User a.one@test.com
        $idUserAOne = $this->createTestUser('a.one@test.com');

        // create Team A
        $idTeamA = $this->createTestTeam();

        // assign User a.one@test.com to Team A
        $permissions = ["developer", "custodian.dar.manager"];
        $this->assignUserToTeam($idTeamA, $idUserAOne, $permissions);

        // generate jwt for user a.one@test.com
        $jwtUserAOne = $this->getTestUserAuthorization('a.one@test.com');

        // user a.one@test.com create Tool AOne with Team A
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $responseCreateTool = $this->json(
            'POST',
            '/api/v1/tools',
            [
                'mongo_object_id' => strtolower(Str::random(24)),
                'name' => 'Tool A',
                'url' => fake()->url(),
                'description' => fake()->sentence(),
                'results_insights' => fake()->sentence(),
                'license' => $licenseId,
                'tech_stack' => fake()->words(3, true),
                'category_id' => $categoryId,
                'user_id' => $idUserAOne,
                'team_id' => $idTeamA,
                'enabled' => 1,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserAOne,
            ]
        );

        // success
        $responseCreateTool->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $toolId = $responseCreateTool['data'];

        // create User a.two@test.com
        $idUserATwo = $this->createTestUser('a.two@test.com');

        // assign User a.one@test.com to Team A
        $permissions = ["custodian.team.admin"];
        $this->assignUserToTeam($idTeamA, $idUserATwo, $permissions);

        // generate jwt for user a.one@test.com
        $jwtUserATwo = $this->getTestUserAuthorization('a.two@test.com');

        // update tool
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $responseUpdate = $this->json(
            'PUT',
            '/api/v1/tools/' . $toolId,
            [
                'name' => 'Tool A',
                'user_id' => $idUserATwo,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'enabled' => 1,
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
                'status' => 'ACTIVE'
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserATwo,
            ]
        );

        // error
        $responseUpdate->assertStatus(401);
    }

    public function test_v1_update_tool_A_by_another_user_from_another_team_with_no_success()
    {
        // create User a.one@test.com
        $idUserAOne = $this->createTestUser('a.one@test.com');

        // create Team A
        $idTeamA = $this->createTestTeam();

        // assign User a.one@test.com to Team A
        $permissions = ["developer", "custodian.dar.manager"];
        $this->assignUserToTeam($idTeamA, $idUserAOne, $permissions);

        // generate jwt for user a.one@test.com
        $jwtUserAOne = $this->getTestUserAuthorization('a.one@test.com');

        // user a.one@test.com create Tool AOne with Team A
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $responseCreateTool = $this->json(
            'POST',
            '/api/v1/tools',
            [
                'mongo_object_id' => strtolower(Str::random(24)),
                'name' => 'Tool A',
                'url' => fake()->url(),
                'description' => fake()->sentence(),
                'results_insights' => fake()->sentence(),
                'license' => $licenseId,
                'tech_stack' => fake()->words(3, true),
                'category_id' => $categoryId,
                'user_id' => $idUserAOne,
                'team_id' => $idTeamA,
                'enabled' => 1,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserAOne,
            ]
        );

        // success
        $responseCreateTool->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $toolId = $responseCreateTool['data'];

        // create User b.one@test.com
        $idUserBOne = $this->createTestUser('b.one@test.com');

        // create Team B
        $idTeamB = $this->createTestTeam();

        // assign User a.one@test.com to Team B
        $permissions = ["custodian.team.admin"];
        $this->assignUserToTeam($idTeamB, $idUserBOne, $permissions);

        // generate jwt for user b.one@test.com
        $jwtUserBOne = $this->getTestUserAuthorization('b.one@test.com');

        // update tool
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $categoryId = Category::where('enabled', 1)->get()->random()->id;
        $tags = Tag::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $datasets = Dataset::where('status', Dataset::STATUS_ACTIVE)->inRandomOrder()->take(3)->select('id')->get()->toArray();
        $programmingLanguags = ProgrammingLanguage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $programmingPackages = ProgrammingPackage::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $typeCategories = TypeCategory::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $durs = Dur::inRandomOrder()->select('id')->take(2)->get()->toArray();
        $generatedPublications = $this->generatePublications();
        $generatedCollections = $this->generateCollections();
        $responseUpdate = $this->json(
            'PUT',
            '/api/v1/tools/' . $toolId,
            [
                'name' => 'Tool A',
                'user_id' => $idUserBOne,
                'tag' => convertArrayToArrayWithKeyName($tags, 'id'),
                'dataset' => $this->generateDatasets($datasets),
                'programming_language' => convertArrayToArrayWithKeyName($programmingLanguags, 'id'),
                'programming_package' => convertArrayToArrayWithKeyName($programmingPackages, 'id'),
                'type_category' => convertArrayToArrayWithKeyName($typeCategories, 'id'),
                'enabled' => 1,
                'publications' => $generatedPublications,
                'durs' => convertArrayToArrayWithKeyName($durs, 'id'),
                'collections' => $generatedCollections,
                'any_dataset' => false,
                'status' => 'ACTIVE'
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtUserBOne,
            ]
        );

        // error
        $responseUpdate->assertStatus(401);
    }

    public function assignUserToTeam(int $teamId, int $userId, array $permissions)
    {
        $url = 'api/v1/teams/' . $teamId . '/users';
        $payload = [
            "userId" => $userId,
            "roles" => $permissions,
        ];

        $response = $this->json('POST', $url, $payload, $this->header);
        $response->assertStatus(201);

        return;
    }

    public function createTestTeam()
    {
        $userNotificationId = User::all()->random()->id;
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => fake()->words(3, true),
                'email' => null,
                'user_id' => $userNotificationId,
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header
        );
        $responseNotification->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

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
                'member_of' => fake()->randomElement([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                    TeamMemberOf::NCS,
                ]),
                'contact_point' => fake()->unique()->safeEmail(),
                'application_form_updated_by' => fake()->words(3, true),
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$responseNotification['data']],
                'is_question_bank' => 1,
                'users' => [],
                'url' => 'https://fakeimg.pl/350x200/ff0000/000',
                'introduction' => fake()->sentence(),
                'dar_modal_content' => fake()->sentence(),
                'service' => 'https://service.local/test',
            ],
            $this->header
        );
        $responseTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return $responseTeam['data'];
    }


    public function createTestUser(string $email)
    {
        $password = 'Passw@rd1!';
        $provider = 'service';
        $userNotificationId = User::all()->random()->id;
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => fake()->words(3, true),
                'email' => null,
                'user_id' => $userNotificationId,
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header
        );
        $responseNotification->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $responseUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => fake()->firstName(),
                'lastname' => fake()->lastName(),
                'email' => $email,
                'secondary_email' => 'just.test.1234567890@test.com',
                'preferred_email' => 'primary',
                'password' => $password,
                'sector_id' => 1,
                'organisation' => 'Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'provider' => $provider,
                'providerid' => '123456',
                'bio' => fake()->words(2, true),
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/75697342",
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => fake()->randomNumber(9, true),
                'mongo_object_id' => strtolower(Str::random(24)),
                'notifications' => [$responseNotification['data']],
            ],
            $this->header
        );
        $responseNotification->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return $responseUser['data'];
    }

    public function getTestUserAuthorization(string $email)
    {
        $password = 'Passw@rd1!';
        $response = $this->json('POST', '/api/v1/auth', [
            'email' => $email,
            'password' => $password,
        ], [
            'Accept' => 'application/json'
        ]);

        return $response['access_token'];
    }

    private function generatePublications()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $temp = [];
            $temp['id'] = Publication::all()->random()->id;
            $return[] = $temp;
        }

        // remove duplicate entries - doesn't use array_unique directly as that fails for multi-d arrays.
        return array_map("unserialize", array_unique(array_map("serialize", $return)));
    }

    private function generateCollections()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $temp = [];
            $temp['id'] = Collection::all()->random()->id;
            $return[] = $temp;
        }

        // remove duplicate entries - doesn't use array_unique directly as that fails for multi-d arrays.
        return array_map("unserialize", array_unique(array_map("serialize", $return)));
    }

    private function generateDatasets(array $datasets)
    {
        $response = [];
        foreach ($datasets as $dataset) {
            $response[] = [
                'id' => $dataset['id'],
                'link_type' => fake()->randomElement(['Used on', 'Other', 'Used in (Tool)', 'Derived from (Tool)'])
            ];
        }

        return $response;
    }

}
