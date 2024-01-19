<?php

namespace Tests\Feature;

use Config;
use ReflectionClass;

use Tests\TestCase;
use Tests\Traits\MockExternalApis;
use Database\Seeders\CategorySeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\ToolSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\ApplicationSeeder;

use App\Models\Application;
use App\Models\Tool;
use App\Models\ToolHasTag;
use App\Http\Requests\ToolRequest;
use App\Http\Controllers\Api\V1\ToolController;

use Illuminate\Foundation\Testing\RefreshDatabase;

class ToolIntegrationTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    const TEST_URL = '/api/v1/integrations/tools';

    protected $header = [];
    
    protected $integration = [];

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
            ToolSeeder::class,
            TagSeeder::class,
            ApplicationSeeder::class,
        ]);
        $this->header = [
            'Accept' => 'application/json',
        ];
        $this->integration = Application::find(1)->first();
        $this->body = [
            "app_id" => $this->integration['app_id'], 
            "client_id" => $this->integration['client_id']
        ];
    }

    /**
     * Get All Tools with success
     * 
     * @return void
     */
    public function test_get_all_tools_with_success(): void
    {
        $countTool = Tool::where('enabled', 1)->count();
        $response = $this->json('GET', self::TEST_URL, $this->body, $this->header);
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
                    'user_id',
                    'enabled',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'user',
                    'tag',
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
        $tools = Tool::where('enabled', 1)->first();
        $response = $this->json('GET', self::TEST_URL . '/' . $tools->id, $this->body, $this->header);
        $this->assertCount(1, $response['data']);
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
                    'user_id',
                    'enabled',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'user',
                    'tag',
                ]
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
        $countBefore = Tool::withTrashed()->count();
        $countPivotBefore = ToolHasTag::all()->count();
        $mockData = array(
            "mongo_object_id" => "5ece82082abda8b3a06f1941",
            "name" => "Similique sapiente est vero eum.",
            "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
            "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
            "license" => "Inventore omnis aut laudantium vel alias.",
            "tech_stack" => "Cumque molestias excepturi quam at.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => array(1, 2),
            "enabled" => 1,
        );

        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            array_merge($mockData, $this->body),
            $this->header
        );

        $countAfter = Tool::withTrashed()->count();
        $countPivotAfter = ToolHasTag::all()->count();
        $countNewRow = $countAfter - $countBefore;
        $countPivotNewRows = $countPivotAfter - $countPivotBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $this->assertEquals(
            2,
            $countPivotNewRows,
            "actual value is equal to expected"
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
        // insert
        $mockDataIns = array(
            "mongo_object_id" => "5ece82082abda8b3a06f1941",
            "name" => "Similique sapiente est vero eum.",
            "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
            "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
            "license" => "Inventore omnis aut laudantium vel alias.",
            "tech_stack" => "Cumque molestias excepturi quam at.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => array(1),
            "enabled" => 1,
        );
        $responseIns = $this->json(
            'POST',
            self::TEST_URL . '/',
            array_merge($mockDataIns, $this->body),
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
            "license" => "Modi tenetur et et perferendis.",
            "tech_stack" => "Dolor accusamus rerum numquam et.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => array(2),
            "enabled" => 1,
        );

        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $toolIdInsert,
            array_merge($mockDataUpdate, $this->body),
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
        $this->assertEquals($responseUpdate['data']['license'], $mockDataUpdate['license']);
        $this->assertEquals($responseUpdate['data']['tech_stack'], $mockDataUpdate['tech_stack']);
        $this->assertEquals($responseUpdate['data']['user_id'], $mockDataUpdate['user_id']);
        $this->assertEquals($responseUpdate['data']['enabled'], $mockDataUpdate['enabled']);

        $toolHasTags = ToolHasTag::where('tool_id', $toolIdInsert)->get();

        $this->assertEquals(count($toolHasTags), 1);

        $this->assertEquals($toolHasTags[0]['tag_id'], 2);
    }

    /**
     * Edit Tool with sucess by id
     *
     * @return void
     */
    public function test_edit_tool_with_success(): void
    {
        // insert
        $mockDataIns = array(
            "mongo_object_id" => "5ece82082abda8b3a06f1941",
            "name" => "Similique sapiente est vero eum.",
            "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
            "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
            "license" => "Inventore omnis aut laudantium vel alias.",
            "tech_stack" => "Cumque molestias excepturi quam at.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => array(1),
            "enabled" => 1,
        );
        $responseIns = $this->json(
            'POST',
            self::TEST_URL . '/',
            array_merge($mockDataIns, $this->body),
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
            "license" => "Modi tenetur et et perferendis.",
            "tech_stack" => "Dolor accusamus rerum numquam et.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => array(2),
            "enabled" => 1,
        );

        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $toolIdInsert,
            array_merge($mockDataUpdate, $this->body),
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
        $this->assertEquals($responseUpdate['data']['license'], $mockDataUpdate['license']);
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
            "enabled" => 0,
        );

        $responseEdit1 = $this->json(
                'PATCH',
                self::TEST_URL . '/' . $toolIdInsert,
                array_merge($mockDataEdit1, $this->body),
                $this->header
            );

        $responseEdit1->assertJsonStructure([
            'message',
            'data',
        ]);
        $responseEdit1->assertStatus(200);
        $this->assertEquals($responseEdit1['data']['name'], $mockDataEdit1['name']);
        $this->assertEquals($responseEdit1['data']['description'], $mockDataEdit1['description']);
        $this->assertEquals($responseEdit1['data']['enabled'], $mockDataEdit1['enabled']);

        // edit 
        $mockDataEdit2 = array(
            "url" => "http://dach.com/odio-facilis-ex-culpa-e2",
            "license" => "Modi tenetur et et perferendis. e2",
            "tech_stack" => "Dolor accusamus rerum numquam et. e2",
        );

        $responseEdit2 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $toolIdInsert,
            array_merge($mockDataEdit2, $this->body),
            $this->header
        );

        $responseEdit2->assertJsonStructure([
            'message',
            'data',
        ]);
        $responseEdit2->assertStatus(200);
        $this->assertEquals($responseEdit2['data']['url'], $mockDataEdit2['url']);
        $this->assertEquals($responseEdit2['data']['license'], $mockDataEdit2['license']);
        $this->assertEquals($responseEdit2['data']['tech_stack'], $mockDataEdit2['tech_stack']);
    }

    /**
     * Update Tool with success by id and generate an exception
     *
     * @return void
     */
    public function test_update_tool_and_generate_exception(): void
    {
        $mockData = array(
            "mongo_object_id" => "5ece82082abda8b3a06f1941",
            "name" => "Similique sapiente est vero eum.",
            "url" => "http://steuber.info/itaque-rerum-quia-et-odit-dolores-quia-enim",
            "description" => "Quod maiores id qui iusto. Aut qui velit qui aut nisi et officia. Ab inventore dolores ut quia quo. Quae veritatis fugiat ad vel.",
            "license" => "Inventore omnis aut laudantium vel alias.",
            "tech_stack" => "Cumque molestias excepturi quam at.",
            "category_id" => 1,
            "user_id" => 1,
            "tag" => array(1, 2),
            "enabled" => 1,
        );
        $id = 10000;

        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            array_merge($mockData, $this->body),
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
        $response = $this->json('DELETE', self::TEST_URL . '/' . $tools->id, $this->body, $this->header);
        $countAfter = Tool::onlyTrashed()->count();

        $response->assertStatus(200);

        $this->assertEquals(
            $countBefore+1,
            $countAfter,
            "actual value is equals to expected"
        );
    }
}
