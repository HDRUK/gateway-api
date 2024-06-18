<?php

namespace Tests\Feature;

use Mockery;
use App\Services\PubSubService;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Topic;
use Google\Cloud\PubSub\Message;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Tests\CreatesApplication;


class PubSubServiceTest extends TestCase
{
    protected $pubSubService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::shouldReceive('get')
            ->with('services.googlepubsub.project_id')
            ->andReturn('test-project-id');

        Config::shouldReceive('get')
            ->with('services.googlepubsub.pubsub_topic')
            ->andReturn('test-topic');

        $this->pubSubService = new PubSubService();
    }

    public function testPublishMessage()
    {
        $data = ['message' => 'test message'];

        $pubSubClientMock = $this->createMock(PubSubClient::class);
        $topicMock = $this->createMock(Topic::class);

        $pubSubClientMock->expects($this->once())
            ->method('topic')
            ->with('test-topic')
            ->willReturn($topicMock);

        $topicMock->expects($this->once())
            ->method('publish')
            ->with($this->callback(function ($message) use ($data) {
                $messageData = json_decode($message->data(), true);
                return $messageData === $data;
            }));

        $this->pubSubService->setPubSubClient($pubSubClientMock);

        $this->pubSubService->publishMessage($data);
    }

}
