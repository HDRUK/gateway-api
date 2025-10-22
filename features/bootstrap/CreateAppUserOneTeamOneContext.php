<?php

namespace App\Behat\Context;

use Exception;
use Faker\Factory as Faker;
use PHPUnit\Framework\Assert;
use Behat\Behat\Context\Context;
use App\Models\Application;
use Illuminate\Support\Facades\Http;

/**
 * Defines application create features from the specific context.
 */
class CreateAppUserOneTeamOneContext implements Context
{
    private $baseUri;
    private $accessToken;
    private $faker;
    private $response;
    private $userOne;
    private $teamOne;

    /**
     * Initializes context.
     */
    public function __construct()
    {
        $this->baseUri = config('app.url');
        $this->faker = Faker::create();
        $this->accessToken = SharedContext::get('jwt.user.one');
        $this->userOne = SharedContext::get('user.one');
        $this->teamOne = SharedContext::get('team.one');
    }

    /**
     * @Given I send a POST request to :path with user one credentials for team one
     */
    public function iSendAPostRequestToWithUserOneCredentialsForTeamOne($path)
    {
        try {
            $url = $this->baseUri . $path;

            $payload = [
                'name' => $this->faker->words(10, true),
                'image_link' => htmlentities($this->faker->imageUrl(640, 480, 'animals', true), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                'description' => $this->faker->text(),
                'team_id' => $this->teamOne['id'],
                'user_id' => $this->userOne['id'],
                'enabled' => true,
                'permissions' => [
                    7,
                    8,
                    9,
                    10,
                ],
                'notifications' => [
                    $this->faker->unique()->safeEmail()
                ],
            ];

            $this->response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
            ])->post($url, $payload);
        } catch (GuzzleException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @Then I should receive a successful response for create application with status code :statusCode with user one credentials for team one
     */
    public function iShouldReceiveASuccessfulResponseForCreateApplicationWithStatusCodeWithUserOneCredentialsForTeamOne($statusCode)
    {
        Assert::assertEquals(
            $statusCode,
            $this->response->getStatusCode(),
            "Expected status code {$statusCode}, and received {$this->response->getStatusCode()}."
        );
    }

    /**
     * @Then the response should contain the newly created application information with user one credentials for team one
     */
    public function theResponseShouldContainTheNewlyCreatedApplicationInformationWithUserOneCredentialsForTeamOne()
    {
        $responseData = json_decode($this->response->body(), true);
        if (!isset($responseData['data'])) {
            throw new Exception("The response does not contain the expected key.");
        }
    }

    /**
     * @Then I verify the application is created in the applications table with user one credentials for team one
     */
    public function iVerifyTheApplicationIsCreatedInTheApplicationsTableWithUserOneCredentialsForTeamOne()
    {
        $responseData = json_decode($this->response->body(), true);
        $applicationId = $responseData['data']['id'];

        $application = Application::where('id', $applicationId)->first();

        if (!$application) {
            throw new Exception("The application was not found in the database.");
        }

        SharedContext::set('application', [
            'id' => $responseData['data']['id'],
            'app_id' => $responseData['data']['app_id'],
            'client_id' => $responseData['data']['client_id'],
        ]);
    }
}
