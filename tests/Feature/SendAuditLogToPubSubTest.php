<?php

namespace Tests\Feature;

use Mockery;
use Tests\TestCase;
use Illuminate\Support\Env;
use Google\Cloud\PubSub\Topic;
use App\Services\PubSubService;
use App\Jobs\SendAuditLogToPubSub;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;

class SendAuditLogToPubSubTest extends TestCase
{
    public function testPubSubServiceCalledWithCorrectData()
    {
        Config::set('GOOGLE_CLOUD_PROJECT_ID', 'fake-project-id');
        Config::set('GOOGLE_CLOUD_PUBSUB_TOPIC', 'fake-topic-name');
        Config::set('GOOGLE_CLOUD_PUBSUB_ENABLED', true);

        Queue::fake();

        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('publish')
            ->with(Mockery::on(function ($data) {
                $this->assertEquals(json_encode(['message' => 'fake test message']), $data['data']);
                return true;
            }));

        $pubSubClient = Mockery::mock('overload:Google\Cloud\PubSub\PubSubClient');
        $pubSubClient->shouldReceive('topic')
            ->andReturn($topic);

        SendAuditLogToPubSub::dispatch(['message' => 'fake test message']);

        Queue::assertPushed(SendAuditLogToPubSub::class);
    }
}
