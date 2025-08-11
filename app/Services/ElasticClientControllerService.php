<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use App\Http\Traits\LoggingContext;

class ElasticClientControllerService
{
    use LoggingContext;

    protected $baseUrl;
    protected $username;
    protected $password;
    protected $timeout = 10;
    protected $verifySSL = false;
    private ?array $loggingContext = null;

    public function __construct()
    {
        $this->baseUrl = config('database.connections.elasticsearch.host');
        $this->username = config('services.elasticclient.user');
        $this->password = config('services.elasticclient.password');
        $this->timeout = config('services.elasticclient.timeout', 10);
        $this->verifySSL = config('services.elasticclient.verify_ssl', false);
        $this->loggingContext = $this->getLoggingContext(\request());
        $this->loggingContext['method_name'] = class_basename($this);
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
            $this->loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;
            \Log::info('Reindexing elastic doc ' . $params['index'] . ' ' . $params['id'], $this->loggingContext);

            $response = $this->makeRequest()
                ->post($url, $params['body']);

            $response->throw();
            return $response;
        } catch (RequestException $e) {

            $response = $e->response; // Get the response object from the exception
            $headers = $response ? $response->headers() : [];

            // Optionally, log the headers for debugging
            $this->loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;
            \Log::error('Failed to index document', array_merge([
                'url' => $url,
                'params' => $params,
                'headers' => $headers,
                'error' => $e->getMessage(),
            ], $this->loggingContext));

            throw new \Exception(
                'Failed to index document: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (\Exception $e) {
            // General exception handling for any other unexpected errors
            $this->loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;
            \Log::error('An unexpected error occurred', array_merge([
                'url' => $url,
                'error' => $e->getMessage(),
            ], $this->loggingContext));

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

        $this->loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;
        \Log::info('Bulk reindexing elastic', $this->loggingContext);

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

            $this->loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;
            \Log::error('Failed to index document', array_merge([
                'url' => $url,
                'bulkData' => $bulkData,
                'error' => $e->getMessage(),
            ], $this->loggingContext));

            throw new \Exception(
                'Failed to index document: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (\Exception $e) {
            // General exception handling for any other unexpected errors
            $this->loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;
            \Log::error('An unexpected error occurred', array_merge([
                'url' => $url,
                'error' => $e->getMessage(),
            ], $this->loggingContext));

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

            $this->loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;
            \Log::error('Failed to delete document: ' . $e->getMessage(), $this->loggingContext);

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
    public function countDocuments(string $index, ?string $field = null, ?string $value = null, bool $exact = false)
    {
        $url = $this->baseUrl . '/' . $index . '/_count';

        if ($field && $value) {
            $query = $exact
                ? ['term' => [$field => $value]]
                : ['match' => [$field => $value]];
        } else {
            $query = ['match_all' => new \stdClass()];
        }

        $response = $this->makeRequest()
            ->post($url, ['query' => $query]);

        if ($response->successful()) {
            return $response->json('count');
        }

        return 0;
    }
}
