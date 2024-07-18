<?php
namespace App\Services;

use Config;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\MessageBuilder;

class CloudPubSubService
{
    protected $pubSubClient;

    public function __construct()
    {
        $this->pubSubClient = new PubSubClient([
            'projectId' => Config::get('services.googlepubsub.project_id'),
        ]);
    }

    public function publish(array $data)
    {
        $topic = $this->pubSubClient->topic(Config::get('services.googlepubsub.pubsub_topic'));
        $message = (new MessageBuilder)->setData(json_encode($data))->build();
        return $topic->publish($message);
    }

    public function clearPubSubClient()
    {
        $this->pubSubClient = null;
    }
}