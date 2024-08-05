<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;
use App\Http\Enums\TeamMemberOf;
use App\Models\Team;
use Database\Seeders\MinimalUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private $accessToken = '';

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
        ]);

        $response = $this->postJson('api/v1/auth', [
            'email' => 'developers@hdruk.ac.uk',
            'password' => 'Watch26Task?',
        ]);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $content = $response->decodeResponseJson();
        $this->accessToken = $content['access_token'];

    }

    /**
     * List all teams.
     *
     * @return void
     */
    public function test_the_application_can_list_teams()
    {
        $response = $this->get('api/v1/teams', [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

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
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => 'joe@example.com',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
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
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        $response = $this->get('api/v1/teams/' .$teamId, [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);
        $content = $response->decodeResponseJson();

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $this->assertEquals($content['data']['notifications'][0]['notification_type'], 'applicationSubmitted');

        $teamPid = $content['data']['pid'];
        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $teamPid);

        // delete the team created
        $responseDelete = $this->json(
            'DELETE',
            'api/v1/teams/' . $teamId . '?deletePermanently=true',
            [],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
        ]);
    }

    /**
     * Create a new team.
     *
     * @return void
     */
    public function test_the_application_can_create_a_team()
    {
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => 'joe@example.com',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
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
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
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
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
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
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => 'joe@example.com',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
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
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();

        $teamId = $content['data'];

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
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
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

        // delete the team created
        $responseDelete = $this->json(
            'DELETE',
            'api/v1/teams/' . $teamId . '?deletePermanently=true',
            [],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
        ]);
    }

    /**
     * Edit a team.
     *
     * @return void
     */
    public function test_the_application_can_edit_a_team()
    {
        // create notification
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => 'joe@example.com',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
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
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
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
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
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
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
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
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
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
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
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
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => 'joe@example.com',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
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
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
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
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);
    }
}
