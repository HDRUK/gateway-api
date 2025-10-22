<?php

namespace App\Behat\Context;

use Exception;
use App\Models\Role;
use App\Models\User;
use App\Models\TeamHasUser;
use Faker\Factory as Faker;
use PHPUnit\Framework\Assert;
use App\Models\TeamUserHasRole;
use Behat\Behat\Context\Context;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Defines Remove Role user two from team one using user one credentials in specific context.
 */
class RemoveRoleUserTwoFromTeamOneByUserOneContext implements Context
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
        $this->baseUri = config('app.url');
        $this->faker = Faker::create();
        $this->userOne = SharedContext::get('user.one');
        $this->userTwo = SharedContext::get('user.two');
        $this->teamOne = SharedContext::get('team.one');
        $this->accessToken = SharedContext::get('jwt.user.one');
    }
    /**
    * @Given I have user one with credentials I send a update request to path with team one and user two for update roles
    */
    public function iHaveUserOneWithCredentialsISendAUpdateRequestToPathWithTeamOneAndUserTwoForUpdateRoles()
    {
        try {
            File::put(storage_path('logs/email.log'), '');

            $payload = [
                'roles' => [
                    'custodian.dar.manager' => false,
                ],
            ];

            $url = $this->baseUri . '/api/v1/teams/' . $this->teamOne['id'] . '/users/' . $this->userTwo['id'];

            $this->response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
            ])->put($url, $payload);
        } catch (GuzzleException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @Then I should receive a successful response with status code :statusCode after update role user one for team one using user one credentials
     */
    public function iShouldReceiveASuccessfulResponseWithStatusCodeAfterUpdateRoleUserOneForTeamOneUsingUserOneCredentials($statusCode)
    {
        Assert::assertEquals(
            $statusCode,
            $this->response->getStatusCode(),
            "Expected status code {$statusCode}, and received {$this->response->getStatusCode()}."
        );
    }

    /**
     * @Then I verify that the role for user one was updated for team one using user one credentials in email
     */
    public function iVerifyThatTheRoleForUserOneWasUpdatedForTeamOneUsingUserOneCredentialsInEmail()
    {
        sleep(1); // Give some time for the queue to process

        $recipient = $this->userTwo['email'];
        $log = File::get(storage_path('logs/email.log'));
        if (!str_contains($log, $recipient)) {
            throw new \Exception("Email to {$recipient} not found in log");
        }
    }

    /**
     * @Then I verify that the role for user one was updated for team one using user one credentials in database
     */
    public function iVerifyThatTheRoleForUserOneWasUpdatedForTeamOneUsingUserOneCredentialsInDatabase()
    {
        $teamUser = TeamHasUser::where([
            'team_id' => $this->teamOne['id'],
            'user_id' => $this->userTwo['id'],
        ])->first();

        if (!$teamUser) {
            throw new Exception("The user {$this->userTwo['id']} was not found in the database assigned with team {$this->teamOne['id']}.");
        }

        $role = Role::where([
            'name' => 'custodian.dar.manager',
        ])->first();

        if (!$role) {
            throw new Exception("The role 'custodian.dar.manager' was not found in the database.");
        }

        $teamUserRole = TeamUserHasRole::where([
            'team_has_user_id' => $teamUser->id,
            'role_id' => $role->id,
        ])->first();

        if ($teamUserRole) {
            throw new Exception("The role 'custodian.dar.manager' was found for user {$this->userTwo['id']} who is assigned with team {$this->teamOne['id']}.");
        }
    }
}
