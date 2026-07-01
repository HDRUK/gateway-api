<?php

namespace App\Services;

use Typesense\Client;

class TypesenseService
{
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
