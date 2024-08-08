<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticClientControllerService
{
    public function __construct()
    {
        //
    }

    /**
     * Configures and builds the client used to reindex ElasticSearch
     * Note: this client is defined here in the MMC facade, making it convenient
     * to mock during testing
     *
     * @return Client
     */
    public function getElasticClient()
    {
        return ClientBuilder::create()
            ->setHosts(config('database.connections.elasticsearch.hosts'))
            ->setSSLVerification(config('services.elasticclient.verify_ssl'))
            ->setBasicAuthentication(
                config('services.elasticclient.user'),
                config('services.elasticclient.password')
            )->build();
    }
}
