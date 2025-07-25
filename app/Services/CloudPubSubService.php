<?php

namespace App\Services;

use Config;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\MessageBuilder;

// use App\Http\Traits\LoggingContext;

class CloudPubSubService
{
    // use LoggingContext;

    protected $pubSubClient;
    // private ?array $loggingContext = null;

    public function __construct()
    {
        $this->pubSubClient = new PubSubClient([
            'projectId' => Config::get('services.googlepubsub.project_id'),
        ]);
        // $this->loggingContext = $this->getLoggingContext(\request());
        // $this->loggingContext['method_name'] = class_basename($this);
    }

    public function setPubSubClient(PubSubClient $pubSubClient)
    {
        $this->pubSubClient = $pubSubClient;
    }

    public function publishMessage(array $data)
    {
        if (Config::get('services.googlepubsub.enabled') === false) {
            return;
        }

        // \Log::info('Publishing message to PubSub', $this->loggingContext);

        $topic = $this->pubSubClient->topic(Config::get('services.googlepubsub.pubsub_topic'));
        $message = (new MessageBuilder())->setData(json_encode($data))->build();
        return $topic->publish($message);
    }

    public function clearPubSubClient()
    {
        $this->pubSubClient = null;
    }
}
