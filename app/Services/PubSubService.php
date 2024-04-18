<?php
namespace App\Services;

use Config;
use Google\Cloud\PubSub\PubSubClient;

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
        // if (!Config::get('services.googlepubsub.pubsub_enabled')) {
        //     return;
        // }

        $topic = $this->pubSubClient->topic(Config::get('services.googlepubsub.pubsub_topic'));
        $topic->publish(['data' => json_encode($data)]);
    }
}