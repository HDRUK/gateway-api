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
 * Defines assign user two to team one with custodian dar manager role features from the specific context.
 */
class AssignUsertwoTeamOneCDarManagerContext implements Context
{
    private $baseUri;
    private $accessToken;
    private $userTwo;
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
        $this->accessToken = SharedContext::get('jwt.user.one');
        $this->userTwo = SharedContext::get('user.two');
        $this->teamOne = SharedContext::get('team.one');
    }

    /**
     * @Given I send a POST request to path with team one and user two and assigning :role role custodian dar manager
     */
    public function iSendAPostRequestToPathWithTeamOneAndUserTwoAndAssigningRoleCustodianDarManager($role)
    {
        try {
            File::put(storage_path('logs/email.log'), '');

            $arrayRole = [$role];
            $payload = [
                "userId" => $this->userTwo['id'],
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
     * @Then I should receive a successful response with status code :statusCode after user two was assigned to the team one like custodian dar manager
     */
    public function iShouldReceiveASuccessfulResponseWithStatusCodeAfterUserTwoWasAssignedToTheTeamOneLikeCustodianDarManager($statusCode)
    {
        Assert::assertEquals(
            $statusCode,
            $this->response->getStatusCode(),
            "Expected status code {$statusCode}, and received {$this->response->getStatusCode()}."
        );
    }

    /**
     * @Then I verify that the user one assigned to team one with role custodian dar manager should receive an email
     */
    public function iVerifyThatTheUserOneAssignedToTeamOneWithRoleCustodianDarManagerShouldReceiveAnEmail()
    {
        sleep(1); // Give some time for the queue to process

        $recipient = $this->userTwo['email'];
        $log = File::get(storage_path('logs/email.log'));
        if (!str_contains($log, $recipient)) {
            throw new \Exception("Email to {$recipient} not found in log");
        }
    }

    /**
     * @Then I verify that the user two should be a member of team one like custodian dar manager
     */
    public function iVerifyThatTheUserTwoShouldBeAMemberOfTeamOneLikeCustodianDarManager()
    {
        $userTeam = TeamHasUser::where([
            'user_id' => $this->userTwo['id'],
            'team_id' => $this->teamOne['id'],
        ])->first();

        if (!$userTeam) {
            throw new Exception("The user two assinged to team one was not found in the database.");
        }
    }

    /**
     * @Then I verify that the user two assigned to team one should have the :role role custodian dar manager
     */
    public function iVerifyThatTheUserTwoAssignedToTeamOneShouldHaveTheRoleCustodianDarManager($role)
    {
        $roles = Role::where([
            'name' => $role,
        ])->first();

        $found = false;
        if ($roles) {
            $userTeam = TeamHasUser::where([
                'user_id' => $this->userTwo['id'],
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
            throw new Exception("The user two assinged to team one with role was not found in the database.");
        }
    }
}
