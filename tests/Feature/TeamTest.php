<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;
use App\Models\Team;
use App\Models\Alias;
use App\Models\Dataset;
use App\Http\Enums\TeamMemberOf;
use Database\Seeders\AliasSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\MinimalUserSeeder;
use MetadataManagementController as MMC;
use Database\Seeders\SpatialCoverageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];
    protected $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        Team::flushEventListeners();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
            AliasSeeder::class,
        ]);

        $this->metadata = $this->getMetadata();
    }

    /**
     * List all teams.
     *
     * @return void
     */
    public function test_the_application_can_list_teams()
    {
        $response = $this->get('api/v1/teams', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'name',
                        'enabled',
                        'allows_messaging',
                        'workflow_enabled',
                        'access_requests_management',
                        'uses_5_safes',
                        'is_admin',
                        'team_logo',
                        'member_of',
                        'contact_point',
                        'application_form_updated_by',
                        'application_form_updated_on',
                        'users',
                        'notifications',
                        'is_question_bank',
                        'is_provider',
                        'url',
                        'introduction',
                        'dar_modal_content',
                        'service',
                    ],
                ],
            ]);
    }

    /**
     * List a particular team.
     *
     * @return void
     */
    public function test_the_application_can_show_one_team()
    {
        $aliasId = Alias::all()->random()->id;

        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
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
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
                'is_question_bank' => 1,
                'users' => [],
                'url' => 'https://fakeimg.pl/350x200/ff0000/000',
                'introduction' => fake()->sentence(),
                'dar_modal_content' => fake()->sentence(),
                'service' => 'https://service.local/test',
                'aliases' => [$aliasId],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        $response = $this->get('api/v1/teams/' .$teamId, $this->header);
        $content = $response->decodeResponseJson();

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'enabled',
                    'allows_messaging',
                    'workflow_enabled',
                    'access_requests_management',
                    'uses_5_safes',
                    'is_admin',
                    'team_logo',
                    'member_of',
                    'contact_point',
                    'application_form_updated_by',
                    'application_form_updated_on',
                    'users',
                    'notifications',
                    'is_question_bank',
                    'is_provider',
                    'url',
                    'introduction',
                    'dar_modal_content',
                    'service',
                ],
            ]);

        $this->assertEquals($content['data']['notifications'][0]['notification_type'], 'applicationSubmitted');

        $teamPid = $content['data']['pid'];
        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $teamPid);

        // delete the team created
        $responseDelete = $this->json(
            'DELETE',
            'api/v1/teams/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
        ]);
    }

    /**
     * Show summary of a team.
     *
     * @return void
     */
    public function test_the_application_can_show_team_summary()
    {
        $id = Team::where(['enabled' => 1])->first()->id;
        $response = $this->get('api/v1/teams/' . $id . '/summary', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'is_provider',
                    'team_logo',
                    'introduction',
                    'datasets',
                    'durs',
                    'tools',
                    'publications',
                    'collections',
                ],
            ]);
    }

    /**
     * Create a new team.
     *
     * @return void
     */
    public function test_the_application_can_create_a_team()
    {
        $aliasId = Alias::all()->random()->id;

        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
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
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
                'is_question_bank' => 0,
                'users' => [],
                'url' => 'https://fakeimg.pl/350x200/ff0000/000',
                'introduction' => fake()->sentence(),
                'dar_modal_content' => fake()->sentence(),
                'service' => 'https://service.local/test',
                'aliases' => [$aliasId],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // delete the team created
        $responseDelete = $this->json(
            'DELETE',
            'api/v1/teams/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
        ]);
    }

    /**
     * Update an existing team.
     *
     * @return void
     */
    public function test_the_application_can_update_a_team()
    {
        MMC::spy();
        $aliasId = Alias::all()->random()->id;

        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create a team for us to update within this
        // test
        $response = $this->json(
            'POST',
            'api/v1/teams',
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'enabled' => 0,
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
                'is_question_bank' => 0,
                'users' => [],
                'url' => 'https://fakeimg.pl/350x200/ff0000/000',
                'introduction' => fake()->sentence(),
                'dar_modal_content' => fake()->sentence(),
                'service' => 'https://service.local/test',
                'aliases' => [$aliasId],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();

        $teamId = $content['data'];

        // Add a dataset associated with the team
        $responseCreateDataset = $this->json(
            'POST',
            '/api/v1/datasets',
            [
                'team_id' => $teamId,
                'user_id' => 1,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $responseCreateDataset->assertStatus(201);
        $datasetId = $responseCreateDataset->decodeResponseJson()['data'];

        // Finally, update this team with new details
        $updateTeamName = 'Updated Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}');
        $response = $this->json(
            'PUT',
            'api/v1/teams/' . $teamId,
            [
                'name' => $updateTeamName,
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 0,
                'is_admin' => 1,
                'member_of' => 'HUB',
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:45:41',
                'notifications' => [$notificationID],
                'is_question_bank' => 1,
                'users' => [],
                'introduction' => fake()->sentence(),
                'dar_modal_content' => fake()->sentence(),
                'aliases' => [$aliasId],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();

        $this->assertEquals($content['data']['enabled'], 1);
        $this->assertEquals($content['data']['member_of'], 'HUB');
        $this->assertEquals($content['data']['name'], $updateTeamName);

        MMC::shouldReceive("validateDataModelType")->andReturn(true);

        $responseGetDataset = $this->json(
            'GET',
            '/api/v1/datasets' . '/' . $datasetId,
            [],
            $this->header
        );
        $responseGetDataset->assertStatus(200);
        // Note BES 09/10/24
        // Removing the creation of a new version due to memory load
        // $datasetContent = $responseGetDataset->decodeResponseJson();
        // $this->assertEquals(
        //     $datasetContent['data']['versions'][0]['metadata']['metadata']['required']['version'],
        //     '2.0.0'
        // );

        // delete the team created
        $responseDelete = $this->json(
            'DELETE',
            'api/v1/teams/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );

        $existsDatasets = Dataset::where('team_id', $teamId)->select('id')->first();

        if (!is_null($existsDatasets)) {
            $responseDelete->assertStatus(500)
                    ->assertJsonStructure([
                        'message',
                    ]);
        } else {
            $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
                ->assertJsonStructure([
                    'message',
                ]);
        }

    }

    /**
     * Edit a team.
     *
     * @return void
     */
    public function test_the_application_can_edit_a_team()
    {
        $aliasId = Alias::all()->random()->id;

        // create notification
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // create team
        $responseCreate = $this->json(
            'POST',
            'api/v1/teams',
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'enabled' => 0,
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
                'is_question_bank' => 1,
                'users' => [],
                'url' => 'https://fakeimg.pl/350x200/ff0000/000',
                'introduction' => fake()->sentence(),
                'dar_modal_content' => fake()->sentence(),
                'service' => 'https://service.local/test',
                'aliases' => [$aliasId],
            ],
            $this->header
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $id = $contentCreate['data'];

        // update team
        $updateTeamName = 'Updated Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}');
        $updateTeamMemberOf = fake()->randomElement([
            TeamMemberOf::ALLIANCE,
            TeamMemberOf::HUB,
            TeamMemberOf::OTHER,
        ]);
        $responseUpdate = $this->json(
            'PUT',
            'api/v1/teams/' . $id,
            [
                'name' => $updateTeamName,
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 0,
                'is_admin' => 1,
                'member_of' => 'HUB',
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:45:41',
                'notifications' => [$notificationID],
                'is_question_bank' => 0,
                'users' => [],
                'introduction' => fake()->sentence(),
                'dar_modal_content' => fake()->sentence(),
                'aliases' => [$aliasId],
            ],
            $this->header
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentUpdate = $responseUpdate->decodeResponseJson();

        $this->assertEquals($contentUpdate['data']['enabled'], 1);
        $this->assertEquals($contentUpdate['data']['member_of'], 'HUB');
        $this->assertEquals($contentUpdate['data']['name'], $updateTeamName);

        // edit team
        $responseEdit1 = $this->json(
            'PATCH',
            'api/v1/teams/' . $id,
            [
                'name' => 'Updated Test Team e1',
                'allows_messaging' => 0,
            ],
            $this->header
        );

        $responseEdit1->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);
        $contentEdit1 = $responseEdit1->decodeResponseJson();

        $this->assertEquals($contentEdit1['data']['allows_messaging'], 0);
        $this->assertEquals($contentEdit1['data']['name'], 'Updated Test Team e1');


        // edit team
        $responseEdit2 = $this->json(
            'PATCH',
            'api/v1/teams/' . $id,
            [
                'enabled' => 0,
            ],
            $this->header
        );

        $responseEdit2->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit2 = $responseEdit2->decodeResponseJson();

        $this->assertEquals($contentEdit2['data']['enabled'], 0);

        // delete the team created
        $responseDelete = $this->json(
            'DELETE',
            'api/v1/teams/' . $id . '?deletePermanently=true',
            [],
            $this->header
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
        ]);
    }

    /**
     * Delete a team.
     *
     * @return void
     */
    public function test_the_application_can_delete_a_team()
    {
        $aliasId = Alias::all()->random()->id;

        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create a team for us to delete within this
        // test
        $response = $this->json(
            'POST',
            'api/v1/teams',
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'enabled' => 0,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => fake()->randomElement([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                ]),
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
                'is_question_bank' => 0,
                'users' => [],
                'url' => 'https://fakeimg.pl/350x200/ff0000/000',
                'introduction' => fake()->sentence(),
                'aliases' => [$aliasId],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();

        // Finally, delete the team we just created
        $responseDelete = $this->json(
            'DELETE',
            'api/v1/teams/' . $content['data'] . '?deletePermanently=true',
            [],
            $this->header
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);
    }
}
