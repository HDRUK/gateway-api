<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    private $accessToken = '';

    public function setUp() :void
    {
        parent::setUp();

        $this->seed();

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
                'message',
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
                'name' => 'A. Test Team', 
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
                'notifications' => [$notificationID],
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
                
        $response = $this->get('api/v1/teams/' . $content['data'], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);
        $content = $response->decodeResponseJson();

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))    
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $this->assertEquals($content['data'][0]['notifications'][0]['notification_type'], 'applicationSubmitted');
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
                'name' => 'A. Test Team', 
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
                'notifications' => [$notificationID],
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
                'name' => 'Created Test Team', 
                'enabled' => 0,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
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
                
        // Finally, update this team with new details
        $response = $this->json(
            'PATCH', 
            'api/v1/teams/' . $content['data'],
            [  
                'name' => 'Updated Test Team', 
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 0,
                'is_admin' => 1,
                'member_of' => 1002,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:45:41',
                'notifications' => [$notificationID],
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
        $this->assertEquals($content['data']['member_of'], 1002);
        $this->assertEquals($content['data']['name'], 'Updated Test Team');
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
                'name' => 'Deletable Test Team', 
                'enabled' => 0,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
                
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
        $response = $this->json(
            'DELETE', 
            'api/v1/teams/' . $content['data'], 
            [],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );     

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);
    }
}
