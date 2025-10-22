<?php

namespace App\Behat\Context;

use Exception;
use Faker\Factory as Faker;
use PHPUnit\Framework\Assert;
use Behat\Behat\Context\Context;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;

/**
 * Defines team create features from the specific context.
 */
class CreateNotificationContext implements Context
{
    private $baseUri;
    private $accessToken;
    private $faker;
    private $response;
    private $notificationId;

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
     * @Given I send a POST request to :uri with type :type for create a new notification
     */
    public function iSendAPostRequestToWithTypeForCreateANewNotification($path, $type)
    {
        try {
            $payload = [
                'notification_type' => $type,
                'message' => $this->faker->words(5, true),
                'opt_in' => 1,
                'enabled' => 1,
                'email' => $this->faker->unique()->safeEmail(),
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
     * @Then I should receive a successful response for create notification with status code :statusCode
     */
    public function iShouldReceiveASuccessfulResponseForCreateNotificationWithStatusCode($statusCode)
    {
        Assert::assertEquals(
            $statusCode,
            $this->response->getStatusCode(),
            "Expected status code {$statusCode}, and received {$this->response->getStatusCode()}."
        );
    }

    /**
     * @Then the response should contain the newly created notification information
     */
    public function theResponseShouldContainTheNewlyCreatedNotificationInformation()
    {
        $responseData = json_decode($this->response->body(), true);
        $this->notificationId = $responseData['data'];
        if (!isset($responseData['data'])) {
            throw new Exception("The response does not contain the expected key.");
        }
    }

    /**
     * @Then I verify the notification is created in the notifications table
     */
    public function iVerifyTheNotificationIsCreatedInTheNotificationsTable()
    {
        $team = Notification::where('id', $this->notificationId)->first();

        if (!$team) {
            throw new Exception("The team was not found in the database.");
        }
        SharedContext::set('notification.id', $this->notificationId);
    }

}
