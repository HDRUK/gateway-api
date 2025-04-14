<?php

namespace Tests\Feature\Observers;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Http\Enums\TeamMemberOf;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\SpatialCoverageSeeder;

class TeamObserverTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];
    protected $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        // Team::flushEventListeners();
        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
        ]);

        $this->metadata = $this->getMetadata();
    }

    public function testTeamObserverCreateOneTeam()
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

    public function testTeamObserverUpdateTeam()
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

        // MMC::spy();

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

        $responseGetDataset = $this->json(
            'GET',
            '/api/v1/datasets' . '/' . $datasetId,
            [],
            $this->header
        );
        $responseGetDataset->assertStatus(200);

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
}
