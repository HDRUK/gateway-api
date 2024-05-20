<?php

namespace App\Behat\Context;

use Faker\Factory as Faker;
use PHPUnit\Framework\Assert;
use Behat\Behat\Context\Context;
use App\Models\AuthorisationCode;
use Behat\Gherkin\Node\TableNode;
use App\Behat\Context\SharedContext;
use Behat\Gherkin\Node\PyStringNode;
use Illuminate\Support\Facades\Http;
use App\Behat\Context\FeatureContext;
use App\Http\Controllers\JwtController;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

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
        $this->baseUri = env('APP_URL');
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
     * @Then I verify the access token exists in the authorisation_codes table for user one credentials
     */
    public function iVerifyTheAccessTokenExistsInTheAuthorisationCodesTableForUserOneCredentials()
    {
        $authorisationCodes = AuthorisationCode::where([
            'jwt' => $this->accessToken,
        ])->first();
        Assert::assertTrue((bool) $authorisationCodes, 'we should verify the access token exists for user one');
        SharedContext::set('jwt.user_one', $this->accessToken);
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
