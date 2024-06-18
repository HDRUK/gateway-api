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
    protected function setUp(): void
    {
        parent::setUp();

        // Set environment variables for the test
        putenv('GOOGLE_CLOUD_PROJECT_ID=fake-project-id');
        putenv('GOOGLE_CLOUD_PUBSUB_TOPIC=fake-topic-name');
        putenv('GOOGLE_CLOUD_PUBSUB_ENABLED=true');
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testPublishMessage()
    {
        $pubSubClientMock = Mockery::mock('overload:Google\Cloud\PubSub\PubSubClient');

        $topicMock = Mockery::mock(Topic::class);

        $pubSubClientMock->shouldReceive('topic')
            ->once()
            ->with(env('GOOGLE_CLOUD_PUBSUB_TOPIC'))
            ->andReturn($topicMock);

        // Expect the publish method to be called with the correct parameter
        $topicMock->shouldReceive('publish')
            ->once()
            ->with(Mockery::on(function ($message) {
                $messageBuilder = new MessageBuilder();
                $expectedMessage = $messageBuilder->setData(json_encode(['test' => 'Test message']))->build();
                return $message == $expectedMessage;
            }));

        $service = new PubSubService($pubSubClientMock);
        $service->publishMessage(['test' => 'Test message']);
    }


    // public function testPublishMessageWhenEnabled()
    // {
    //     Config::set('GOOGLE_CLOUD_PROJECT_ID', 'fake-project-id');
    //     Config::set('GOOGLE_CLOUD_PUBSUB_TOPIC', 'fake-topic-name');
    //     Config::set('GOOGLE_CLOUD_PUBSUB_ENABLED', true);

    //     // Mock the Topic class
    //     $topic = Mockery::mock(Topic::class);
    //     $topic->shouldReceive('publish')
    //         ->once()
    //         ->with(Mockery::on(function ($data) {
    //             $this->assertEquals(json_encode(['message' => 'fake test message']), $data['data']);
    //             return true;
    //         }));

    //     // Mock the PubSubClient and make it return the mocked Topic
    //     $pubSubClient = Mockery::mock('overload:Google\Cloud\PubSub\PubSubClient');
    //     $pubSubClient->shouldReceive('topic')
    //         ->once()
    //         ->andReturn($topic);

    //     $message = ['message' => 'fake test message'];
    //     $service = new PubSubService();
    //     $service->publishMessage(json_encode($message));
    // }
}
