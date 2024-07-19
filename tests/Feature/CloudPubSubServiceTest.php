<?php

namespace Tests\Feature;

use Config;
use Mockery;
use Tests\TestCase;
use Google\Cloud\PubSub\Topic;
use App\Services\CloudPubSubService;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\MessageBuilder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CloudPubSubServiceTest extends TestCase
{
    public function test_publish_message()
    {
        Config::set('services.googlepubsub.project_id', 'fake-project-id');
        Config::set('services.googlepubsub.pubsub_topic', 'fake-topic-name');
        Config::set('services.googlepubsub.enabled', true);

        $data = ['test' => 'Test message'];
        $messageId = 'message-id';

        // Mock the Topic class
        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('publish')
            ->once()
            ->with(Mockery::on(function ($message) {
                $messageBuilder = new MessageBuilder();
                $expectedMessage = $messageBuilder->setData(json_encode(['test' => 'Test message']))->build();
                return $message == $expectedMessage;
            }))
            ->andReturn($messageId);

        // Mock the PubSubClient and make it return the mocked Topic
        $pubSubClient = Mockery::mock(PubSubClient::class);
        $pubSubClient->shouldReceive('topic')
            ->once()
            ->with('fake-topic-name')
            ->andReturn($topic);

        // Use reflection to inject the mock into CloudPubSubService
        $service = new CloudPubSubService();
        $reflection = new \ReflectionClass($service);

        $pubSubClientProperty = $reflection->getProperty('pubSubClient');
        $pubSubClientProperty->setAccessible(true);
        $pubSubClientProperty->setValue($service, $pubSubClient);

        $result = $service->publishMessage($data);

        $this->assertEquals($messageId, $result);
    }
}
