<?php

namespace Tests\Feature;

use Config;
use Mockery;
use Tests\TestCase;
use Google\Cloud\PubSub\Topic;
use Google\Cloud\PubSub\Message;
use App\Services\CloudPubSubService;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\MessageBuilder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CloudPubSubServiceTest extends TestCase
{
    protected $pubSubClientMock;
    protected $topicMock;
    protected $messageMock;
    protected $cloudPubSubService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pubSubClientMock = $this->createMock(PubSubClient::class);
        $this->topicMock = $this->createMock(Topic::class);
        $this->messageMock = $this->createMock(Message::class);

        Config::shouldReceive('get')
            ->with('services.googlepubsub.project_id')
            ->andReturn('test-project-id');
        
        Config::shouldReceive('get')
            ->with('services.googlepubsub.enabled')
            ->andReturn(true);
        
        Config::shouldReceive('get')
            ->with('services.googlepubsub.pubsub_topic')
            ->andReturn('test-topic');

        $this->cloudPubSubService = new CloudPubSubService();
        $this->cloudPubSubService->setPubSubClient($this->pubSubClientMock);
    }

    public function testPublishMessage()
    {
        $data = ['message' => 'test'];

        $this->pubSubClientMock->expects($this->once())
            ->method('topic')
            ->with('test-topic')
            ->willReturn($this->topicMock);

        $this->topicMock->expects($this->once())
            ->method('publish')
            ->with($this->callback(function ($message) {
                $data = json_decode($message->data(), true);
                return $data['message'] === 'test';
            }))
            ->willReturn(true);

        $result = $this->cloudPubSubService->publishMessage($data);

        $this->assertTrue($result);
    }
}
