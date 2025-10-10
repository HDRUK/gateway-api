<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Http\Enums\TeamMemberOf;
use App\Models\TeamHasNotification;
use Tests\Traits\MockExternalApis;


class TeamNotificationTest extends TestCase
{
    
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
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
                'member_of' => fake()->randomElement([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                    TeamMemberOf::NCS,
                ]),
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [],
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
        $teamId = $contentCreateTeam['data'];

        // create notification for team
        $responseTeamNotification = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/notifications',
            [
                'user_notification_status' => true,
                'team_notification_status' => true,
                'team_emails' => [
                    'djakubowski@example.org',
                    'trohan.bla@example.net',
                    'willis.rice.bla@example.net',
                    'amir.swift.bla@example.org',
                ],
            ],
            $this->header,
        );

        $responseTeamNotification->assertStatus(201)
            ->assertJsonStructure([
                'message',
            ]);

        $teamNotifications = TeamHasNotification::where('team_id', $teamId)->get();

        $this->assertCount(4, $teamNotifications);

        // delete the team created
        $responseDelete = $this->json(
            'DELETE',
            'api/v1/teams/' . $teamId . '?deletePermanently=true',
            [],
            $this->header,
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
        ]);

    }

    public function test_get_notification_for_team_with_success()
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
                'member_of' => fake()->randomElement([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                    TeamMemberOf::NCS,
                ]),
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [],
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
        $teamId = $contentCreateTeam['data'];

        // create notification for team
        $responseTeamNotification = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/notifications',
            [
                'user_notification_status' => true,
                'team_notification_status' => true,
                'team_emails' => [
                    'djakubowski@example.org',
                    'trohan.bla@example.net',
                    'willis.rice.bla@example.net',
                    'amir.swift.bla@example.org',
                ],
            ],
            $this->header,
        );

        $responseTeamNotification->assertStatus(201)
            ->assertJsonStructure([
                'message',
            ]);

        $teamNotifications = TeamHasNotification::where('team_id', $teamId)->get();

        $this->assertCount(4, $teamNotifications);

        // get notification by team id and user id from jwt
        $responseGetTeamNotification = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/notifications',
            [],
            $this->header,
        );
        $responseGetTeamNotification->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        // delete the team created
        $responseDelete = $this->json(
            'DELETE',
            'api/v1/teams/' . $teamId . '?deletePermanently=true',
            [],
            $this->header,
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
        ]);
    }
}
