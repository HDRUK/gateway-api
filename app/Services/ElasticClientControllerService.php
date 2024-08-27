<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Http;

class ElasticClientControllerService
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $verifySSL;

    public function __construct()
    {
        $this->baseUrl = config('database.connections.elasticsearch.host');
        $this->username = config('services.elasticclient.user');
        $this->password = config('services.elasticclient.password');
        $this->verifySSL = config('services.elasticclient.verify_ssl');
    }

    /**
     * Makes an HTTP POST request to index a document in Elasticsearch
     *
     * @param array $params
     * @return \Illuminate\Http\Client\Response
     */
    public function indexDocument(array $params)
    {
        $url = $this->baseUrl . '/' . $params['index'] . '/_doc/' . $params['id'];
        $response = Http::withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withOptions(['verify' => $this->verifySSL])
            ->post($url, $params['body']);

        return $response;
    }

    /**
     * Deletes all documents in a specific Elasticsearch index.
     *
     * @param string $index
     * @return \Illuminate\Http\Client\Response
     */
    public function deleteAllDocuments(string $index)
    {
        $url = $this->baseUrl . '/' . $index . '/_delete_by_query';
        $query = [
            'query' => [
                'match_all' => new \stdClass()
            ]
        ];

        $response = Http::withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withOptions(['verify' => $this->verifySSL])
            ->post($url, $query);

        if ($response->successful()) {
            return $response->json('deleted');
        }

        return 0;  // Return 0 if the request fails
    }

    /**
     * Counts the number of documents in a specific Elasticsearch index.
     *
     * @param string $index
     * @return int
     */
    public function countDocuments(string $index)
    {
        $url = $this->baseUrl . '/' . $index . '/_count';

        $response = Http::withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withOptions(['verify' => $this->verifySSL])
            ->post($url, ['query' => ['match_all' => new \stdClass()]]);

        if ($response->successful()) {
            return $response->json('count');
        }

        return 0;  // Return 0 if the request fails
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
