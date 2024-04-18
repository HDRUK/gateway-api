<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\PubSubService;
use Google\Cloud\PubSub\Topic;
use Mockery;
use Illuminate\Support\Facades\Config;

class PubSubServiceTest extends TestCase
{
    public function testPublishMessage()
    {
        Config::set('GOOGLE_CLOUD_PROJECT_ID', 'fake-project-id');
        Config::set('GOOGLE_CLOUD_PUBSUB_TOPIC', 'fake-topic-name');

        // Mock the Topic class
        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('publish')
            ->once()
            ->with(Mockery::on(function ($data) {
                $this->assertEquals(json_encode(['message' => 'fake test message']), $data['data']);
                return true;
            }));

        // Mock the PubSubClient and make it return the mocked Topic
        $pubSubClient = Mockery::mock('overload:Google\Cloud\PubSub\PubSubClient');
        $pubSubClient->shouldReceive('topic')
                    ->once()
                    ->andReturn($topic);

        $service = new PubSubService();
        $service->publishMessage(['message' => 'fake test message']);
    }
}
