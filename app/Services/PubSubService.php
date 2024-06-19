<?php
namespace App\Services;

use Config;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\MessageBuilder;

class PubSubService
{
    protected $pubSubClient;

    public function __construct()
    {
        $this->pubSubClient = new PubSubClient([
            'projectId' => Config::get('services.googlepubsub.project_id'),
        ]);
    }

    public function publishMessage(array $data)
    {
        \Log::debug(json_encode(['$this->pubSubClient', $this->pubSubClient]));
        $topic = $this->pubSubClient->topic(Config::get('services.googlepubsub.pubsub_topic'));
        \Log::debug(json_encode(['$topic', $topic]));
        $message = (new MessageBuilder)->setData(json_encode($data))->build();
        \Log::debug(json_encode(['$message', $message]));
        $publish = $topic->publish($message);
        \Log::debug(json_encode(['$publish', $publish]));
    }
}