<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;

use App\Models\Tool;
use ReflectionClass;
use App\Models\License;
use App\Models\DurHasTool;
use App\Models\ToolHasTag;
use App\Models\Publication;
use App\Models\ToolHasTypeCategory;
use App\Models\DatasetVersionHasTool;
use App\Models\ToolHasProgrammingPackage;
use App\Models\ToolHasProgrammingLanguage;
use Database\Seeders\DurSeeder;
use Database\Seeders\TagSeeder;
use Tests\Traits\Authorization;
use Database\Seeders\ToolSeeder;
use App\Http\Requests\ToolRequest;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\KeywordSeeder;
use Database\Seeders\LicenseSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DurHasToolSeeder;
use Database\Seeders\ToolHasTagSeeder;
use Database\Seeders\ApplicationSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\PublicationSeeder;
use Database\Seeders\TypeCategorySeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\DurHasPublicationSeeder;
use Database\Seeders\ProgrammingPackageSeeder;
use Database\Seeders\PublicationHasToolSeeder;
use Database\Seeders\DatasetVersionHasToolSeeder;
use App\Http\Controllers\Api\V1\ToolController;
use Database\Seeders\ProgrammingLanguageSeeder;
use Database\Seeders\PublicationHasDatasetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ToolTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    const TEST_URL = '/api/v1/tools';

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
            CategorySeeder::class,
            ProgrammingLanguageSeeder::class,
            ProgrammingPackageSeeder::class,
            LicenseSeeder::class,
            TagSeeder::class,
            KeywordSeeder::class,
            TypeCategorySeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            ToolSeeder::class,
            ToolHasTagSeeder::class,
            PublicationSeeder::class,
            PublicationHasDatasetSeeder::class,
            PublicationHasToolSeeder::class,
            ApplicationSeeder::class,
            DurSeeder::class,
            DurHasPublicationSeeder::class,
            DurHasToolSeeder::class,
        ]);
    }

    /**
     * Get All Tools with success
     * 
     * @return void
     */
    public function test_get_all_tools_with_success(): void
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
    public function test_get_tool_by_id_with_success(): void
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
                'associated_authors', 
                'contact_address',
                'publications',
                'durs',
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Create new Tool with success
     * 
     * @return void
     */
    public function test_add_new_tool_with_success(): void
    {
        $licenseId = License::where('valid_until', null)->get()->random()->id;
        $countBeforeTool = Tool::withTrashed()->count();
        $countPivotBeforeTag = ToolHasTag::all()->count();
        $countPivotBeforeDatasetVersion = DatasetVersionHasTool::all()->count();
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
            "dataset" => array(1, 2),
            "programming_language" => array(1, 2),
            "programming_package" => array(1, 2),
            "type_category" => array(1, 2),
            "enabled" => 1,
            "publications" => $this->generatePublications(),
        );

        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            $mockData,
            $this->header
        );

        $countAfterTool = Tool::withTrashed()->count();
        $countPivotAfterTag = ToolHasTag::all()->count();
        $countPivotAfterDatasetVersion = DatasetVersionHasTool::all()->count();

        $countNewRowTool = $countAfterTool - $countBeforeTool;
        
        $countPivotNewRowsTag = $countPivotAfterTag - $countPivotBeforeTag;
        $countPivotNewRowsDatasetVersion = $countPivotAfterDatasetVersion - $countPivotBeforeDatasetVersion;

        $this->assertTrue((bool) $countNewRowTool, 'Response was successfully');
        $this->assertEquals(
            2,
            $countPivotNewRowsTag,
            "Tag value is equal to expected"
        );
    
        $this->assertEquals(
            2,
            $countPivotNewRowsDatasetVersion,
            "Dataset Version value is equal to expected"
        );
        $response->assertStatus(201);
    }

    /**
     * Insert data into tool_has_tags table with success
     * 
     * @return void
     */
    public function test_insert_data_in_tool_has_tags(): void
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
    public function test_update_tool_with_success(): void 
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
            "tag" => array(1),
            "programming_language" => array(1, 2),
            "programming_package" => array(1, 2),
            "type_category" => array(1, 2),
            "enabled" => 1,
            "publications" => $this->generatePublications(),
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
            "programming_language" => array(1),
            "programming_package" => array(1),
            "type_category" => array(1),
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

        $toolHasProgrammingLanguages = ToolHasProgrammingLanguage::where('tool_id', $toolIdInsert)->get();
        $this->assertEquals(count($toolHasProgrammingLanguages), 1);
        $this->assertEquals($toolHasProgrammingLanguages[0]['programming_language_id'], 1);

        $toolHasProgrammingPackages = ToolHasProgrammingPackage::where('tool_id', $toolIdInsert)->get();
        $this->assertEquals(count($toolHasProgrammingPackages), 1);
        $this->assertEquals($toolHasProgrammingPackages[0]['programming_package_id'], 1);

        $toolHasTypeCategories = ToolHasTypeCategory::where('tool_id', $toolIdInsert)->get();
        $this->assertEquals(count($toolHasTypeCategories), 1);
        $this->assertEquals($toolHasTypeCategories[0]['type_category_id'], 1);
    }

    /**
     * Edit Tool with sucess by id
     *
     * @return void
     */
    public function test_edit_tool_with_success(): void
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
            "tag" => array(1),
            "programming_language" => array(1),
            "programming_package" => array(1),
            "type_category" => array(1),
            "enabled" => 1,
            "publications" => $this->generatePublications(),
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
     * Update Tool with success by id and generate an exception
     *
     * @return void
     */
    public function test_update_tool_and_generate_exception(): void
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
    public function test_soft_delete_tool_with_success(): void
    {
        $tools = Tool::first();
        $countBefore = Tool::onlyTrashed()->count();
        $response = $this->json('DELETE', self::TEST_URL . '/' . $tools->id, [], $this->header);
        $countAfter = Tool::onlyTrashed()->count();

        $response->assertStatus(200);

        $this->assertEquals(
            $countBefore+1,
            $countAfter,
            "actual value is equals to expected"
        );
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

        return $return;
    }
}
