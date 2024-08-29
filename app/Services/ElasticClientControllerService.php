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
    protected $timeout = 10;
    protected $verifySSL = false;


    public function __construct()
    {
        $this->baseUrl = config('database.connections.elasticsearch.host');
        $this->username = config('services.elasticclient.user');
        $this->password = config('services.elasticclient.password');
        $this->timeout = config('services.elasticclient.timeout', 10);
        $this->verifySSL = config('services.elasticclient.verify_ssl', false);
    }

    /**
     * Creates a reusable HTTP client instance with common configuration.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function makeRequest()
    {
        $request = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                    ])
                   ->withOptions(
                       [
                        'verify' => $this->verifySSL,
                        'timeout' => $this->timeout,
                        'retry' => [
                            'max_retries' => 10,
                            'retry_on_timeout' => true,
                        ]
                    ]
                   )
                   ->connectTimeout($this->timeout);

        if (!empty($this->username) && !empty($this->password)) {
            $request->withBasicAuth($this->username, $this->password);
        }

        return $request;
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

            $response = $e->response; // Get the response object from the exception
            $headers = $response ? $response->headers() : [];

            // Optionally, log the headers for debugging
            \Log::error('Failed to index document', [
                'url' => $url,
                'params' => $params,
                'headers' => $headers,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception(
                'Failed to index document: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (\Exception $e) {
            // General exception handling for any other unexpected errors
            \Log::error('An unexpected error occurred', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception(
                'Failed to index document: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );

        }
    }

    /**
     * Makes an HTTP POST request to index a document in Elasticsearch
     *
     * @param array $paramsArray
     * @return \Illuminate\Http\Client\Response
     */
    public function indexBulk(array $paramsArray)
    {
        $url = $this->baseUrl . '/_bulk';
        $bulkData = '';

        // Construct the bulk request payload
        foreach ($paramsArray as $params) {
            $actionAndMetadata = json_encode([
                'index' => [
                    '_index' => $params['index'],
                    '_id'    => $params['id']
                ]
            ]);
            $document = json_encode($params['body']);

            // Each action/metadata line must be followed by the document data line
            $bulkData .= $actionAndMetadata . "\r\n" . $document . "\r\n";
        }

        try {
            $response = $this->makeRequest()
                ->withBody($bulkData, 'application/x-ndjson')
                ->post($url);

            $response->throw();
            return $response;
        } catch (RequestException $e) {

            \Log::error('Failed to index document', [
                'url' => $url,
                'bulkData' => $bulkData,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception(
                'Failed to index document: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (\Exception $e) {
            // General exception handling for any other unexpected errors
            \Log::error('An unexpected error occurred', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception(
                'Failed to index document: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );

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
     * @return int
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
