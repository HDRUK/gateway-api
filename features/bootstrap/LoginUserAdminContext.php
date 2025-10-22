<?php

namespace App\Behat\Context;

use Exception;
use PHPUnit\Framework\Assert;
use Behat\Behat\Context\Context;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\JwtController;
use GuzzleHttp\Exception\GuzzleException;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Defines application features from the specific context.
 */
class LoginUserAdminContext implements Context
{
    private $baseUri;
    private $email;
    private $password;
    private $response;
    private $accessToken;
    // private $responseBody;

    /**
     * Initializes context.
     */
    public function __construct()
    {
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $this->baseUri = config('app.url');
    }

    /**
     * @Given I am a user with the email :email and password :password
     */
    public function iAmAUserWithTheEmailAndPassword($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * @When I send a POST request to :path with my credentials
     */
    public function iSendAPostRequestToWithMyCredentials($path)
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
     * @Then I should receive a successful response from auth with status code :statusCode
     */
    public function iShouldReceiveASuccessfulResponseFromAuthWithStatusCode($statusCode)
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
     * @Then I verify the access token exists in the authorisation_codes table
     */
    public function iVerifyTheAccessTokenExistsInTheAuthorisationCodesTable()
    {
        $jwtController = new JwtController();
        $jwtController->setJwt($this->accessToken);
        $isValidJwt = $jwtController->isValid();

        Assert::assertTrue((bool) $isValidJwt, 'we should verify the access token is valid');
        SharedContext::set('jwt.admin', $this->accessToken);
    }
}
