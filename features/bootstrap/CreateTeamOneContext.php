<?php

namespace App\Behat\Context;

use Exception;
use App\Models\Team;
use Faker\Factory as Faker;
use PHPUnit\Framework\Assert;
use App\Http\Enums\TeamMemberOf;
use Behat\Behat\Context\Context;
use Illuminate\Support\Facades\Http;

/**
 * Defines team one create features from the specific context.
 */
class CreateTeamOneContext implements Context
{
    private $baseUri;
    private $accessToken;
    private $faker;
    private $response;
    private $teamId;
    private $teamName;
    private $notificationId;

    /**
     * Initializes context.
     */
    public function __construct()
    {
        $this->baseUri = config('app.url');
        $this->faker = Faker::create();
        $this->accessToken = SharedContext::get('jwt.admin');
        $this->notificationId = SharedContext::get('notification.id');
    }

    /**
     * @Given I send a POST request to :uri with team one name :teamName
     */
    public function iSendAPostRequestToWithTeamOneName($path, $teamName)
    {
        try {
            $this->teamName = $teamName;
            $payload = [
                'name' => $teamName,
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => $this->faker->randomElement([
                    TeamMemberOf::ALLIANCE->value,
                    TeamMemberOf::HUB->value,
                    TeamMemberOf::OTHER->value,
                    TeamMemberOf::NCS->value,
                ]),
                'contact_point' => $this->faker->unique()->safeEmail(),
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$this->notificationId],
                'is_question_bank' => 1,
                'users' => [],
            ];

            $url = $this->baseUri . $path;

            $this->response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
            ])->post($url, $payload);
        } catch (GuzzleException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @Then I should receive a successful response with status code :statusCode
     */
    public function iShouldReceiveASuccessfulResponseWithStatusCode($statusCode)
    {
        Assert::assertEquals(
            $statusCode,
            $this->response->getStatusCode(),
            "Expected status code {$statusCode}, and received {$this->response->getStatusCode()}."
        );
    }

    /**
     * @Then the response should contain the newly created team one information
     */
    public function theResponseShouldContainTheNewlyCreatedTeamOneInformation()
    {
        $teamData = json_decode($this->response->body(), true);
        $this->teamId = $teamData['data'];
        if (!isset($teamData['data'])) {
            throw new Exception("The response does not contain the expected key.");
        }
    }

    /**
     * @Then I verify the team one is created in the teams table
     */
    public function iVerifyTheTeamOneIsCreatedInTheTeamsTable()
    {
        $team = Team::where('id', $this->teamId)->first();

        if (!$team) {
            throw new Exception("The team was not found in the database.");
        }

        SharedContext::set('team.one', [
            'id' => $this->teamId,
            'name' => $this->teamName,
        ]);
    }
}
