<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

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
     * Creates a reusable HTTP client instance with common configuration.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function makeRequest()
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withOptions(['verify' => $this->verifySSL]);
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
        try {
            $response = $this->makeRequest()
                ->post($url, $params['body']);

            $response->throw();
            return $response;
        } catch (RequestException $e) {
            throw new \Exception('Failed to index document: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Makes an HTTP DELETE request to delete a document in Elasticsearch.
     *
     * @param array $params
     * @return \Illuminate\Http\Client\Response
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function deleteDocument(array $params)
    {
        $url = $this->baseUrl . '/' . $params['index'] . '/_doc/' . $params['id'];
        try {
            $response = $this->makeRequest()
                ->delete($url);

            $response->throw();
            return $response;
        } catch (RequestException $e) {
            throw new \Exception('Failed to delete document: ' . $e->getMessage(), $e->getCode(), $e);
        }
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

        $response = $this->makeRequest()
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

        $response = $this->makeRequest()
            ->post($url, ['query' => ['match_all' => new \stdClass()]]);

        if ($response->successful()) {
            return $response->json('count');
        }

        return 0;  // Return 0 if the request fails
    }


}
