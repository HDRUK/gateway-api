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
        // $out = new \Symfony\Component\Console\Output\ConsoleOutput();
        // $out->writeln(json_encode($data));
        // $topic = $this->pubSubClient->topic(Config::get('services.googlepubsub.pubsub_topic'));
        // $result = $topic->publish(json_encode($data));
        // $out->writeln(json_encode($result->result));
        $topic = $this->pubSubClient->topic(Config::get('services.googlepubsub.pubsub_topic'));
        // $topic->publish(['data' => json_encode($data)]);
        $topic->publish((new MessageBuilder)->setData(json_encode($data))->build());
    }
}