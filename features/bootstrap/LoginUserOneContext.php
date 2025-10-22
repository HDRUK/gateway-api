<?php

namespace App\Behat\Context;

use Faker\Factory as Faker;
use PHPUnit\Framework\Assert;
use Behat\Behat\Context\Context;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\JwtController;

/**
 * Authentication user one
 * Defines application features from the specific context.
 */
class LoginUserOneContext implements Context
{
    private $baseUri;
    private $faker;
    private $accessToken;
    private $userOne;
    private $userId;
    private $email;
    private $password;
    private $response;

    /**
     * Initializes context.
     */
    public function __construct()
    {
        $this->baseUri = config('app.url');
        $this->faker = Faker::create();
        $this->userOne = SharedContext::get('user.one');
    }

    /**
     * @Given I am user one with email and password
     */
    public function iAmUserOneWithEmailAndPassword()
    {
        $this->userId = $this->userOne['id'];
        $this->email = $this->userOne['email'];
        $this->password = $this->userOne['password'];
    }

    /**
     * @When I send a POST request to :path with user one credentials
     */
    public function iSendAPostRequestToWithUserOneCredentials($path)
    {
        try {
            $url = $this->baseUri . $path;

            $this->response = Http::post($url, [
                'email' => $this->email,
                'password' => $this->password,
            ]);
        } catch (GuzzleException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @Then I should receive a successful response from auth with user one credentials and with status code :statusCode
     */
    public function iShouldReceiveASuccessfulResponseFromAuthWithUserOneCredentialsAndWithStatusCode($statusCode)
    {
        Assert::assertEquals(
            $statusCode,
            $this->response->getStatusCode(),
            "Expected status code {$statusCode}, and received {$this->response->getStatusCode()}."
        );

        $body = json_decode($this->response->body(), true);
        $this->accessToken = $body['access_token'];
    }

    /**
     * @Then I verify the access token contain user one credentials
     */
    public function iVerifyTheAccessTokenContainUserOneCredentials()
    {
        $jwtController = new JwtController();
        $jwtController->setJwt($this->accessToken);
        $isValidJwt = $jwtController->isValid();

        Assert::assertTrue((bool) $isValidJwt, 'we should verify the access token is valid');

        $decodeJwt = $jwtController->decode();

        Assert::assertTrue(
            (bool) ((int) $decodeJwt['user']['id'] === (int) $this->userId),
            'we should verify the access token exists for user one with details'
        );
        SharedContext::set('jwt.user.one', $this->accessToken);
    }
}
