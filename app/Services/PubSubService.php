<?php
namespace App\Services;

use Config;
use Google\Cloud\PubSub\PubSubClient;

class PubSubService
{
    protected $pubSubClient;
    protected $topicName;

    public function __construct()
    {
        $this->pubSubClient = new PubSubClient([
            'projectId' => Config::get('services.googlepubsub.project_id')
        ]);
        $this->topicName = Config::get('services.googlepubsub.pubsub_topic');
    }

    public function publishMessage(array $data)
    {
        $topic = $this->pubSubClient->topic($this->topicName);
        $topic->publish(['data' => json_encode($data)]);
    }
}