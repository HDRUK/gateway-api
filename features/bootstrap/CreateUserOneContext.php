<?php

namespace App\Behat\Context;

use Exception;
use Faker\Factory as Faker;
use PHPUnit\Framework\Assert;
use Behat\Behat\Context\Context;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Defines create user one features from the specific context.
 */
class CreateUserOneContext implements Context
{
    private $baseUri;
    private $accessToken;
    private $faker;
    private $email;
    private $password;
    private $response;
    private $userId;

    /**
     * Initializes context.
     */
    public function __construct()
    {
        $this->baseUri = config('app.url');
        $this->faker = Faker::create();
        $this->accessToken = SharedContext::get('jwt.admin');
    }

    /**
     * @Given I send a POST request to :uri with random user one details
     */
    public function iSendAPostRequestToWithRandomUserOneDetails($path)
    {
        try {
            $this->email = $this->faker->unique()->safeEmail();
            $this->password = $this->faker->regexify('[A-Z]{5}[0-4]{3}');
            $payload = [
                'firstname' => $this->faker->firstName(),
                'lastname' => $this->faker->lastName(),
                'email' => $this->email,
                'password' => $this->password,
                'sector_id' => 1,
                'contact_feedback' => 1,
                'contact_news' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/12345678",
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
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
     * @Then I should receive a successful create user one response with status code :statusCode
     */
    public function iShouldReceiveASuccessfulCreateUserOneResponseWithStatusCode($statusCode)
    {
        Assert::assertEquals(
            $statusCode,
            $this->response->getStatusCode(),
            "Expected status code {$statusCode}, and received {$this->response->getStatusCode()}."
        );
    }

    /**
     * @Then the response should contain the newly created user one information
     */
    public function theResponseShouldContainTheNewlyCreatedUserInformation()
    {
        $responseBody = json_decode($this->response->body(), true);
        $this->userId = $responseBody['data'];
        if (!isset($responseBody['data'])) {
            throw new Exception("The response does not contain the expected key.");
        }
    }

    /**
     * @Then I verify the user one is created in the users table
     */
    public function iVerifyTheTeamIsCreatedInTheTeamsTable()
    {
        $user = User::where('id', $this->userId)->first();

        if (!$user) {
            throw new Exception("The team was not found in the database.");
        }

        SharedContext::set('user.one', [
            'id' => $this->userId,
            'email' => $this->email,
            'password' => $this->password,
        ]);
    }
}
