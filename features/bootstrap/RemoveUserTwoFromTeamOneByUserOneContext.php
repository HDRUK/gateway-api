<?php

namespace App\Behat\Context;

use Exception;
use App\Models\Role;
use App\Models\User;
use App\Jobs\SendEmailJob;
use App\Models\TeamHasUser;
use Faker\Factory as Faker;
use App\Models\EmailTemplate;
use PHPUnit\Framework\Assert;
use App\Models\TeamUserHasRole;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use App\Behat\Context\SharedContext;
use Behat\Gherkin\Node\PyStringNode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Defines Remove user two from team one using user one credentials in specific context.
 */
class RemoveUserTwoFromTeamOneByUserOneContext implements Context
{
    private $baseUri;
    private $userOne;
    private $userTwo;
    private $teamOne;
    private $accessToken;
    private $response;

    /**
     * Initializes context.
     */
    public function __construct()
    {
        $this->baseUri = env('APP_URL');
        $this->faker = Faker::create();
        $this->userOne = SharedContext::get('user.one');
        $this->userTwo = SharedContext::get('user.two');
        $this->teamOne = SharedContext::get('team.one');
        $this->accessToken = SharedContext::get('jwt.user.one');
    }

    /**
     * @Given I have user one with credentials I send a DELETE request to path with team one and user two
     */
    public function iHaveUserOneWithCredentialsISendADeleteRequestToPathWithTeamOneAndUserTwo()
    {
        try {
            $payload = [];

            $url = $this->baseUri . '/api/v1/teams/' . $this->teamOne['id'] . '/users/' . $this->userTwo['id'];

            $this->response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
            ])->delete($url, $payload);
        } catch (GuzzleException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @Then I should receive a successful response with status code :statusCode after remove user one from team one using user one credentials
     */
    public function iShouldReceiveASuccessfulResponseWithStatusCodeAfterRemoveUserOneFromTeamOneUsingUserOneCredentials($statusCode)
    {
        Assert::assertEquals(
            $statusCode, 
            $this->response->getStatusCode(), 
            "Expected status code {$statusCode}, and received {$this->response->getStatusCode()}."
        );
    }
    
    /**
     * @Then I verify that the user one was removed from team one using user one credentials in database
     */
    public function iVerifyThatTheUserOneWasRemovedFromTeamOneUsingUserOneCredentialsInDatabase()
    {
        $teamUser = TeamHasUser::where([
            'team_id' => $this->teamOne['id'],
            'user_id' => $this->userTwo['id'],
        ])->first();

        if ($teamUser) {
            throw new Exception("The user {$this->userTwo['id']} was found in the database assigned with team {$this->teamOne['id']}.");
        }
    }
}