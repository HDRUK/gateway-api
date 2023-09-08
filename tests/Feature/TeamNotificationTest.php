<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Tests\Traits\Authorization;
use App\Models\TeamHasNotification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamNotificationTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

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

    public function test_create_notification_for_team_with_success() 
    {
        // create team
        $responseCreateTeam = $this->json(
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
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreateTeam = $responseCreateTeam->decodeResponseJson();
        $teamId = $contentCreateTeam['data'];

        // create notification for team
        $responseTeamNotification = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here applicationSubmitted',
                'opt_in' => true,
                'enabled' => true,
                'email' => 'joe1@example.com',
            ],
            $this->header,
        );

        $responseTeamNotification->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $teamNotifications = TeamHasNotification::where('team_id', $teamId)->get();

        $this->assertCount(1, $teamNotifications);
    }

    public function test_update_notification_for_team_with_success()
    {
        // create team
        $responseCreateTeam = $this->json(
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
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreateTeam = $responseCreateTeam->decodeResponseJson();
        $teamId = $contentCreateTeam['data'];

        // create notification for team
        $responseTeamNotification = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here applicationSubmitted',
                'opt_in' => true,
                'enabled' => true,
                'email' => 'joe1@example.com',
            ],
            $this->header,
        );

        $responseTeamNotification->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data',
            ]);
        $contentTeamNotification = $responseTeamNotification->decodeResponseJson();
        $notificationId = $contentTeamNotification['data'];

        $teamNotifications = TeamHasNotification::where('team_id', $teamId)->get();

        $this->assertCount(1,  $teamNotifications);

        // update notification for team
        $responseUpdateTeamNotification = $this->json(
            'PUT',
            'api/v1/teams/' . $teamId . '/notifications/' . $notificationId,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here applicationSubmitted',
                'opt_in' => true,
                'enabled' => true,
                'email' => 'joe2@example.com',
            ],
            $this->header,
        );

        $responseUpdateTeamNotification->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $teamNotifications = TeamHasNotification::where('team_id', $teamId)->get();

        $this->assertCount(1,
            $teamNotifications
        );
    }

    public function test_delete_notification_for_team_with_success()
    {
        // create team
        $responseCreateTeam = $this->json(
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
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreateTeam = $responseCreateTeam->decodeResponseJson();
        $teamId = $contentCreateTeam['data'];

        // create notification for team
        $responseTeamNotification = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here applicationSubmitted',
                'opt_in' => true,
                'enabled' => true,
                'email' => 'joe1@example.com',
            ],
            $this->header,
        );

        $responseTeamNotification->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data',
            ]);
        $contentTeamNotification = $responseTeamNotification->decodeResponseJson();
        $notificationId = $contentTeamNotification['data'];

        $teamNotifications = TeamHasNotification::where('team_id', $teamId)->get();

        $this->assertCount(1,  $teamNotifications);

        // update notification for team
        $responseUpdateTeamNotification = $this->json(
            'PUT',
            'api/v1/teams/' . $teamId . '/notifications/' . $notificationId,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here applicationSubmitted',
                'opt_in' => true,
                'enabled' => true,
                'email' => 'joe2@example.com',
            ],
            $this->header,
        );

        $responseUpdateTeamNotification->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $teamNotifications = TeamHasNotification::where('team_id', $teamId)->get();

        $this->assertCount(
            1,
            $teamNotifications
        );

        // delete notification for team
        $responseDeleteTeamNotification = $this->json(
            'DELETE',
            'api/v1/teams/' . $teamId . '/notifications/' . $notificationId,
            [],
            $this->header,
        );

        $responseDeleteTeamNotification->assertStatus(200)
        ->assertJsonStructure([
            'message',
        ]);

        $teamNotifications = TeamHasNotification::where('team_id', $teamId)->get();

        $this->assertCount(
            0,
            $teamNotifications
        );
    }
}
