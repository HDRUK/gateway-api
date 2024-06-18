<?php

namespace Tests\Feature;

use Mockery;
use App\Services\PubSubService;
// use Config;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Topic;
use Google\Cloud\PubSub\Message;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;
// use Config;
// use Google\Cloud\PubSub\MessageBuilder;

class PubSubServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testPublishMessage()
    {
        // Mock the Config facade
        Config::shouldReceive('get')
            ->with('services.googlepubsub.project_id')
            ->andReturn('test-project-id');

        Config::shouldReceive('get')
            ->with('services.googlepubsub.pubsub_topic')
            ->andReturn('test-topic');

        // Mock the PubSubClient and Topic
        $mockTopic = Mockery::mock(Topic::class);
        $mockPubSubClient = Mockery::mock(PubSubClient::class);

        $mockPubSubClient->shouldReceive('topic')
            ->with('test-topic')
            ->andReturn($mockTopic);

        $mockTopic->shouldReceive('publish')
            ->once()
            ->with(Mockery::on(function ($message) {
                $expectedData = ['foo' => 'bar'];
                $messageData = json_decode($message->data(), true);
                return $messageData == $expectedData;
            }));

        // Instantiate the service with the mocked PubSubClient
        $pubSubService = new PubSubService();
        $pubSubService->setPubSubClient($mockPubSubClient);

        // Call the publishMessage method
        $data = ['foo' => 'bar'];
        $pubSubService->publishMessage($data);

        $mockPubSubClient->shouldHaveReceived('topic')
            ->with('test-topic')
            ->once();

        $mockTopic->shouldHaveReceived('publish')
            ->once()
            ->with(Mockery::on(function ($message) use ($data) {
                $messageData = json_decode($message->data(), true);
                return $messageData == $data;
            }));
    }
}
