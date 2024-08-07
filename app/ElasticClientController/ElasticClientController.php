<?php

namespace App\ElasticClientController;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticClientController
{
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
            ->setSSLVerification(env('ELASTICSEARCH_VERIFY_SSL'))
            ->setBasicAuthentication(env('ELASTICSEARCH_USER'), env('ELASTICSEARCH_PASS'))
            ->build();
    }
}
