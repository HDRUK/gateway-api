<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Typesense\Client;

class TypesenseService
{
    /**
     * Default stop word set id, created by createDefaultStopwordsSet() and
     * usable via withStopwords() when building query options/params.
     */
    public const DEFAULT_STOPWORDS_SET_ID = 'common_stopwords';

    /**
     * Common English stop words with little to no search relevance.
     */
    public const DEFAULT_STOPWORDS = [
        'a', 'about', 'above', 'after', 'again', 'against', 'all', 'am', 'an', 'and',
        'any', 'are', "aren't", 'as', 'at', 'be', 'because', 'been', 'before', 'being',
        'below', 'between', 'both', 'but', 'by', "can't", 'cannot', 'could', "couldn't",
        'did', "didn't", 'do', 'does', "doesn't", 'doing', "don't", 'down', 'during',
        'each', 'few', 'for', 'from', 'further', 'had', "hadn't", 'has', "hasn't",
        'have', "haven't", 'having', 'he', "he'd", "he'll", "he's", 'her', 'here',
        "here's", 'hers', 'herself', 'him', 'himself', 'his', 'how', "how's", 'i',
        "i'd", "i'll", "i'm", "i've", 'if', 'in', 'into', 'is', "isn't", 'it', "it's",
        'its', 'itself', "let's", 'me', 'more', 'most', "mustn't", 'my', 'myself',
        'no', 'nor', 'not', 'of', 'off', 'on', 'once', 'only', 'or', 'other', 'ought',
        'our', 'ours', 'ourselves', 'out', 'over', 'own', 'same', "shan't", 'she',
        "she'd", "she'll", "she's", 'should', "shouldn't", 'so', 'some', 'such',
        'than', 'that', "that's", 'the', 'their', 'theirs', 'them', 'themselves',
        'then', 'there', "there's", 'these', 'they', "they'd", "they'll", "they're",
        "they've", 'this', 'those', 'through', 'to', 'too', 'under', 'until', 'up',
        'very', 'was', "wasn't", 'we', "we'd", "we'll", "we're", "we've", 'were',
        "weren't", 'what', "what's", 'when', "when's", 'where', "where's", 'which',
        'while', 'who', "who's", 'whom', 'why', "why's", 'with', "won't", 'would',
        "wouldn't", 'you', "you'd", "you'll", "you're", "you've", 'your', 'yours',
        'yourself', 'yourselves',
    ];

    private Client $client;

    public function __construct(?Client $client = null)
    {
        if ($client !== null) {
            $this->client = $client;
            return;
        }

        $settings = config('scout.typesense.client-settings');

        $this->client = new Client([
            'api_key'                      => $settings['api_key'],
            'nodes'                        => $settings['nodes'],
            'nearest_node'                 => $settings['nearest_node'] ?? null,
            'connection_timeout_seconds'   => $settings['connection_timeout_seconds'] ?? 2,
            'healthcheck_interval_seconds' => $settings['healthcheck_interval_seconds'] ?? 30,
            'num_retries'                  => $settings['num_retries'] ?? 3,
            'retry_interval_seconds'       => $settings['retry_interval_seconds'] ?? 1,
        ]);
    }

    /**
     * Search via Scout — respects the model's typesenseSearchParameters() and
     * typesenseCollectionSchema(). Additional $options are merged on top of the
     * model defaults (query_by, facet_by, filter_by, pagination, etc.).
     *
     * Returns the raw Typesense response array.
     */
    public function search(string $modelClass, string $query, array $options = []): array
    {
        return $this->executeScoutSearch($modelClass, $query ?: '*', $options);
    }

    protected function executeScoutSearch(string $modelClass, string $query, array $options): array
    {
        return $modelClass::search($query)->options($options)->raw();
    }

    /**
     * Search directly against a named collection, bypassing Scout.
     * Use when you need precise control over parameters or are querying
     * a collection that isn't backed by an Eloquent model.
     */
    public function rawSearch(string $collection, string $query, array $params = []): array
    {
        return $this->client->collections[$collection]->documents->search(
            array_merge(['q' => $query ?: '*'], $params)
        );
    }

    /**
     * Runs several searches (each specifying its own 'collection') in a
     * single HTTP round trip. $searches is the list under the 'searches' key
     * of the Typesense multi_search payload; results are returned in the
     * same order under 'results'.
     */
    public function multiSearch(array $searches): array
    {
        return $this->client->multiSearch->perform(['searches' => $searches]);
    }

    /**
     * Returns facet counts for one or more fields on a collection, in the
     * same {field: {buckets: [{key, doc_count}]}} shape the Elasticsearch
     * filter service returns — so callers can drop this straight into an
     * existing bucket-consuming payload.
     */
    public function facetCounts(string $collection, array $fields): array
    {
        if (empty($fields)) {
            return [];
        }

        $result = $this->rawSearch($collection, '*', [
            'facet_by'                      => implode(',', $fields),
            'per_page'                      => 0,
            'max_facet_values'              => 250,
            // Typesense truncates facet values to 100 chars by default —
            // the FE then sends the truncated string as a filter value, which
            // never matches the full stored value. 0 = no truncation.
            'facet_value_truncation_threshold' => 0,
        ]);

        $facets = [];
        foreach ($result['facet_counts'] ?? [] as $facet) {
            $facets[$facet['field_name']] = [
                'buckets' => array_map(fn ($c) => [
                    'key' => $c['value'],
                    'doc_count' => $c['count'],
                ], $facet['counts'] ?? []),
            ];
        }

        return $facets;
    }

    public function listCollections(): array
    {
        return $this->client->collections->retrieve();
    }

    public function collectionExists(string $collection): bool
    {
        try {
            $this->client->collections[$collection]->retrieve();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getCollection(string $collection): array
    {
        return $this->client->collections[$collection]->retrieve();
    }

    /**
     * Create a collection from a raw schema array.
     * To create from a model's typesenseCollectionSchema(), use createCollectionFromModel().
     */
    public function createCollection(array $schema): array
    {
        return $this->client->collections->create($schema);
    }

    /**
     * Create a Typesense collection using the schema defined on a Searchable model.
     */
    public function createCollectionFromModel(string $modelClass): array
    {
        $model = new $modelClass();
        return $this->createCollection($model->typesenseCollectionSchema());
    }

    public function dropCollection(string $collection): array
    {
        return $this->client->collections[$collection]->delete();
    }

    /**
     * Upsert a single document. The document must include the collection's
     * default_sorting_field if one is defined.
     */
    public function upsertDocument(string $collection, array $document): array
    {
        return $this->client->collections[$collection]->documents->upsert($document);
    }

    public function deleteDocument(string $collection, string $id): array
    {
        return $this->client->collections[$collection]->documents[$id]->delete();
    }

    public function getDocument(string $collection, string $id): array
    {
        return $this->client->collections[$collection]->documents[$id]->retrieve();
    }

    /**
     * Returns the number of documents currently indexed in a collection.
     */
    public function documentCount(string $collection): int
    {
        return $this->getCollection($collection)['num_documents'] ?? 0;
    }

    /**
     * Creates (or replaces) the default stop word set used by withStopwords(),
     * containing common English stop words.
     */
    public function createDefaultStopwordsSet(): array
    {
        return $this->upsertStopwordsSet(self::DEFAULT_STOPWORDS_SET_ID, self::DEFAULT_STOPWORDS);
    }

    /**
     * Creates or replaces a named stop word set.
     *
     * The installed Typesense PHP client (v4.9.3) doesn't expose a Stopwords
     * resource, so this calls the Typesense REST API directly.
     */
    public function upsertStopwordsSet(string $id, array $stopwords, string $locale = 'en'): array
    {
        return $this->stopwordsRequest('put', "/stopwords/{$id}", [
            'stopwords' => $stopwords,
            'locale' => $locale,
        ]);
    }

    public function getStopwordsSet(string $id): array
    {
        return $this->stopwordsRequest('get', "/stopwords/{$id}");
    }

    public function listStopwordsSets(): array
    {
        return $this->stopwordsRequest('get', '/stopwords');
    }

    public function deleteStopwordsSet(string $id): array
    {
        return $this->stopwordsRequest('delete', "/stopwords/{$id}");
    }

    /**
     * Merges a stop word set reference into query options/params. Callers pass
     * the result to search()/rawSearch(); an explicit 'stopwords' key already
     * present in $options wins.
     */
    public function withStopwords(array $options, string $setId = self::DEFAULT_STOPWORDS_SET_ID): array
    {
        return array_merge(['stopwords' => $setId], $options);
    }

    private function stopwordsRequest(string $method, string $path, array $body = []): array
    {
        $settings = config('scout.typesense.client-settings');
        $node = $settings['nearest_node'] ?? $settings['nodes'][0];

        $baseUrl = sprintf('%s://%s:%s%s', $node['protocol'], $node['host'], $node['port'], $node['path'] ?? '');

        return Http::withHeaders(['X-TYPESENSE-API-KEY' => $settings['api_key']])
            ->baseUrl($baseUrl)
            ->{$method}($path, $body)
            ->throw()
            ->json();
    }

    public function health(): array
    {
        return $this->client->health->retrieve();
    }

    public function metrics(): array
    {
        return $this->client->metrics->retrieve();
    }

    /**
     * Returns the underlying Typesense client for any operations not covered
     * by the methods above.
     */
    public function client(): Client
    {
        return $this->client;
    }
}
