<?php

namespace Tests\Feature;

use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MetadataManagementController AS MMC;
use Config;

use App\Models\Dataset;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Database\Seeders\KeywordSeeder;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\DatasetVersionSeeder;

use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class FormHydrationTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }
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
            TeamHasUserSeeder::class,
            KeywordSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
        ]);
    }

    public function test_form_hydration_schema(): void
    {
        $response = $this->get('api/v1/form_hydration/schema');
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
         ->assertJsonStructure([
                'data' =>[
                'identifier' => [
                    'required',
                    'title',
                    'description',
                    'examples',
                    'is_list',
                    'is_optional',
                    'types'
                ]
            ]
        ]);
    }

    public function test_form_hydration_schema_with_parameters(): void
    {
        $response = $this->get('api/v1/form_hydration/schema?model=HDRUK');
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
         ->assertJsonStructure([
                'data' =>[
                'identifier' => [
                    'required',
                    'title',
                    'description',
                    'examples',
                    'is_list',
                    'is_optional',
                    'types'
                ]
            ]
        ]);

        $responseOldVersion = $this->get('api/v1/form_hydration/schema?model=HDRUK&version=2.1.2');
        $responseOldVersion->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
         ->assertJsonStructure([
                'data' =>[
                'identifier' => [
                    'required',
                    'title',
                    'description',
                    'examples',
                    'is_list',
                    'is_optional',
                    'types'
                ]
            ]
        ]);

        $this->assertNotEquals($response, $responseOldVersion);


    }
    
    public function test_form_hydration_schema_will_fail(): void
    {
        $response = $this->get('api/v1/form_hydration/schema?model=blah');
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));

        $response = $this->get('api/v1/form_hydration/schema?version=9.9.9');
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
    }

    public function test_get_form_hydration_with_success(): void 
    {
        $teamId = Team::all()->random()->id;
        $userId = User::all()->random()->id;
        $responseCreateDataset = $this->json(
            'POST',
            'api/v1/datasets',
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->getFakeDataset(),
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $response = $this->get('api/v1/form_hydration?team_id=' . $teamId);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                "data" => [
                    "schema_fields",
                    "validation",
                    "defaultValues"
                ]
            ]);
        
    }
    
}
