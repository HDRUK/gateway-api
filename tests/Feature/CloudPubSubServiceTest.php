<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Google\Cloud\PubSub\Topic;
use App\Services\CloudPubSubService;
use Google\Cloud\PubSub\MessageBuilder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CloudPubSubServiceTest extends TestCase
{
    public function test_publish_message()
    {
        Config::set('services.googlepubsub.project_id', 'fake-project-id');
        Config::set('services.googlepubsub.pubsub_topic', 'fake-topic-name');
        Config::set('services.googlepubsub.enabled', true);

        // Mock the Topic class
        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('publish')
            ->once()
            ->with(Mockery::on(function ($message) {
                $messageBuilder = new MessageBuilder();
                $expectedMessage = $messageBuilder->setData(json_encode(['test' => 'Test message']))->build();
                return $message == $expectedMessage;
            }));

        // Mock the PubSubClient and make it return the mocked Topic
        $pubSubClient = Mockery::mock('overload:Google\Cloud\PubSub\PubSubClient');
        $pubSubClient->shouldReceive('topic')
            ->once()
            ->andReturn($topic);

        // Act
        $service = new CloudPubSubService($pubSubClient);
        $result = $service->publishMessage(['test' => 'Test message']);

        // Assert
        $this->assertNull($result); // Assuming your publishMessage doesn't return any value
    }
}
