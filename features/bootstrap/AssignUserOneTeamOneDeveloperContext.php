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
 * Defines assign user one to team one with developer role features from the specific context.
 */
class AssignUserOneTeamOneDeveloperContext implements Context
{
    private $baseUri;
    private $accessToken;
    private $userOne;
    private $teamOne;
    private $faker;
    private $response;

    /**
     * Initializes context.
     */
    public function __construct()
    {
        $this->baseUri = config('app.url');
        $this->faker = Faker::create();
        $this->accessToken = SharedContext::get('jwt.admin');
        $this->userOne = SharedContext::get('user.one');
        $this->teamOne = SharedContext::get('team.one');
    }

    /**
     * @Given I send a POST request to path with team one and user one and assigning :role role
     */
    public function iSendAPostRequestToPathWithTeamOneAndUserOneAndAssigningRole($role)
    {
        try {
            File::put(storage_path('logs/email.log'), '');

            $arrayRole = [$role];
            $payload = [
                "userId" => $this->userOne['id'],
                "roles" => $arrayRole,
            ];

            $url = $this->baseUri . '/api/v1/teams/' . $this->teamOne['id'] . '/users';

            $this->response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
            ])->post($url, $payload);
        } catch (GuzzleException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @Then I should receive a successful response with status code :statusCode after user one was assigned to the team one like developer
     */
    public function iShouldReceiveASuccessfulResponseWithStatusCodeAfterUserOneWasAssignedToTheTeamOneLikeDeveloper($statusCode)
    {
        Assert::assertEquals(
            $statusCode,
            $this->response->getStatusCode(),
            "Expected status code {$statusCode}, and received {$this->response->getStatusCode()}."
        );
    }

    /**
     * @Then I verify that the user one assigned to team one with role developer should receive an email
     */
    public function iVerifyThatTheUserOneAssignedToTeamOneWithRoleDeveloperShouldReceiveAnEmail()
    {
        sleep(1); // Give some time for the queue to process

        $recipient = $this->userOne['email'];
        $log = File::get(storage_path('logs/email.log'));
        if (!str_contains($log, $recipient)) {
            throw new \Exception("Email to {$recipient} not found in log");
        }
    }

    /**
     * @Then I verify that the user one should be a member of team one
     */
    public function iVerifyThatTheUserOneShouldBeAMemberOfTeamOne()
    {
        $userTeam = TeamHasUser::where([
            'user_id' => $this->userOne['id'],
            'team_id' => $this->teamOne['id'],
        ])->first();

        if (!$userTeam) {
            throw new Exception("The user one assinged to team one was not found in the database.");
        }
    }

    /**
     * @Then I verify that the user one assigned to team one should have the :role role
     */
    public function iVerifyThatTheUserOneAssignedToTeamOneShouldHaveTheRole($role)
    {
        $roles = Role::where([
            'name' => $role,
        ])->first();

        $found = false;
        if ($roles) {
            $userTeam = TeamHasUser::where([
                'user_id' => $this->userOne['id'],
                'team_id' => $this->teamOne['id'],
            ])->get();

            foreach ($userTeam as $ut) {
                $userTeamRole = TeamUserHasRole::where([
                    'team_has_user_id' => $ut['id'],
                    'role_id' => $roles->id,
                ])->first();

                if ($userTeamRole) {
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            throw new Exception("The user one assinged to team one with role was not found in the database.");
        }
    }
}


// Given I send a POST request to path with team one and user one and assing "developer" role
// Then I should receive a successful response with status code 200 after user one was assigned to the team one like developer
// Then I verify that the user one should be a member of team one
// And I verify that the user one assigned to team one should have the "developer" role
