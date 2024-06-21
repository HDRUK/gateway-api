<?php

namespace Tests\Feature;

use Mockery;
use Tests\TestCase;
use App\Services\PubSubService;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Topic;
use Google\Cloud\PubSub\MessageBuilder;
use Illuminate\Support\Facades\Config;

class PubSubServiceTest extends TestCase
{
    public function testPublishMessageWhenEnabled()
    {
        Config::set('GOOGLE_CLOUD_PROJECT_ID', 'fake-project-id');
        Config::set('GOOGLE_CLOUD_PUBSUB_TOPIC', 'fake-topic-name');
        Config::set('GOOGLE_CLOUD_PUBSUB_ENABLED', true);

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

        $message = ['message' => 'fake test message'];
        $service = new PubSubService();
        $service->publishMessage(['test' => 'Test message']);
    }
}
