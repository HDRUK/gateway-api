<?php

namespace App\SearchProviders;

use Auditor;
use Http;
use App\Contracts\SearchProvider;
use App\Services\Search\FilterCache;
use App\Services\Search\CollectionHydrator;
use App\Services\Search\DataCustodianHydrator;
use App\Services\Search\DataCustodianNetworkHydrator;
use App\Services\Search\DatasetHydrator;
use App\Services\Search\DataUseHydrator;
use App\Services\Search\PublicationHydrator;
use App\Services\Search\ToolHydrator;

use Laravel\Pennant\Feature;

class HDRUK implements SearchProvider
{
    private const SERVICE_PATH_MAP = [
        'datasets'                => 'datasets',
        'tools'                   => 'tools',
        'collections'             => 'collections',
        'dur'                     => 'dur',
        'publications'            => 'publications',
        'data_custodian_networks' => 'data_custodian_networks',
        'data_custodians'         => 'data_providers',
    ];

    private const FILTER_TYPE_MAP = [
        'datasets'                => ['type' => 'dataset',          'enabledOnly' => true],
        'tools'                   => ['type' => 'tool',             'enabledOnly' => false],
        'collections'             => ['type' => 'collection',       'enabledOnly' => false],
        'dur'                     => ['type' => 'dataUseRegister',  'enabledOnly' => false],
        'publications'            => ['type' => 'paper',            'enabledOnly' => false],
        'data_custodian_networks' => ['type' => 'dataProviderColl', 'enabledOnly' => false],
        'data_custodians'         => ['type' => 'dataProvider',     'enabledOnly' => false],
    ];

    // Types we index ourselves — data_custodians (Team) is owned by external providers.
    private const TYPESENSE_MODEL_MAP = [
        'datasets'                => \App\Models\DatasetVersion::class,
        'tools'                   => \App\Models\Tool::class,
        'collections'             => \App\Models\Collection::class,
        'dur'                     => \App\Models\Dur::class,
        'publications'            => \App\Models\Publication::class,
        'data_custodian_networks' => \App\Models\DataProviderColl::class,
    ];

    // Facet fields per type — must match `facet => true` in the model's typesenseCollectionSchema().
    private const TYPESENSE_FACET_MAP = [
        'datasets'                => 'publisherName,keywords,dataType,geographicLocation,conformsTo',
        'tools'                   => '',
        'collections'             => '',
        'dur'                     => '',
        'publications'            => 'publication_type',
        'data_custodian_networks' => '',
    ];

    // Fields callers may pass as pipe-delimited V2 filters (?publisherName=PIONEER|SAIL).
    // Only known facet fields are forwarded — keeps pagination/sort params out of filter_by.
    private const TYPESENSE_FILTERABLE_MAP = [
        'datasets'   => ['publisherName', 'keywords', 'dataType', 'geographicLocation', 'conformsTo'],
        'publications' => ['publication_type'],
    ];

    public function isDeferred(): bool
    {
        return false;
    }

    public function getFullName(): string
    {
        return 'Health Data Research UK';
    }

    public function getShortName(): string
    {
        return 'HDRUK';
    }

    public function getProviderLogo(): string|null
    {
        return null;
    }

    public function getProviderBlurb(): string|null
    {
        return null;
    }

    public function getSearchURI(string $type): string
    {
        $path = self::SERVICE_PATH_MAP[$type] ?? $type;
        return config('gateway.search_service_url') . "/search/{$path}";
    }

    public function getSupportedTypes(): array
    {
        return array_keys(self::SERVICE_PATH_MAP);
    }

    public function isTypesenseEnabled(): bool
    {
        return Feature::active('TypesenseSearch');
    }

    public function search(string $query, string $type, array $params = []): array
    {
        try {
            if (!$this->isTypesenseEnabled() || !array_key_exists($type, self::TYPESENSE_MODEL_MAP)) {
                return $this->searchViaGoService($query, $type, $params);
            }

            return $this->searchViaTypesense($query, $type, $params);
        } catch (\Throwable $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::error($e->getMessage());
        }

        return ['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []];
    }

    private function searchViaTypesense(string $query, string $type, array $params): array
    {
        $modelClass = self::TYPESENSE_MODEL_MAP[$type];
        $model      = new $modelClass();

        $searchParams = array_merge($model->typesenseSearchParameters(), [
            'per_page' => (int) ($params['limit'] ?? 20),
            'page'     => (int) ($params['page'] ?? 1),
        ]);

        $facetFields = self::TYPESENSE_FACET_MAP[$type] ?? '';
        if ($facetFields !== '') {
            $searchParams['facet_by'] = $facetFields;
        }

        $filterBy = $this->buildFilterBy($type, $params);
        if ($filterBy !== '') {
            $searchParams['filter_by'] = $filterBy;
        }

        $result = app(\App\Services\TypesenseService::class)
            ->rawSearch($model->searchableAs(), $query, $searchParams);

        $elasticHits = $this->mapHitsToElastic($result['hits'] ?? [], $type);
        $hydrated    = $this->hydrate($elasticHits, $type, $params['view_type'] ?? 'full');
        $sorted      = $this->sort($hydrated, $type, $params['sort'] ?? 'score:desc');

        return [
            'hits'         => $sorted,
            'total'        => $result['found'] ?? 0,
            'aggregations' => $this->mapFacetsToAggregations($result['facet_counts'] ?? []),
            'ids'          => array_column($elasticHits, '_id'),
        ];
    }

    private function searchViaGoService(string $query, string $type, array $params): array
    {
        $filterConfig = self::FILTER_TYPE_MAP[$type] ?? ['type' => $type, 'enabledOnly' => false];

        $input         = $params;
        $input['aggs'] = FilterCache::get($filterConfig['type'], $filterConfig['enabledOnly']);

        if ($query !== '') {
            $input['query'] = $query;
        }

        $response = Http::post($this->getSearchURI($type), $input);

        if (!$response->successful()) {
            return ['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []];
        }

        $body = $response->json();

        if (
            !isset($body['hits']) || !is_array($body['hits']) ||
            !isset($body['hits']['hits']) || !is_array($body['hits']['hits']) ||
            !isset($body['hits']['total']['value'])
        ) {
            return ['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []];
        }

        $rawHits = $body['hits']['hits'];
        $total   = $body['hits']['total']['value'];
        $ids     = array_column($rawHits, '_id');
        $aggs    = $body['aggregations'] ?? [];

        $hydrated = $this->hydrate($rawHits, $type, $params['view_type'] ?? 'full');
        $sorted   = $this->sort($hydrated, $type, $params['sort'] ?? 'score:desc');

        return [
            'hits'         => $sorted,
            'total'        => $total,
            'aggregations' => $aggs,
            'ids'          => $ids,
        ];
    }

    /**
     * Convert Typesense hits to the Elasticsearch-shaped array the hydrators expect.
     * Datasets use dataset_id as _id so DatasetHydrator looks up the right parent record.
     */
    private function mapHitsToElastic(array $typesenseHits, string $type): array
    {
        return array_map(function (array $hit) use ($type) {
            $doc = $hit['document'] ?? [];
            $id  = ($type === 'datasets')
                ? ($doc['dataset_id'] ?? $doc['id'])
                : $doc['id'];

            return [
                '_id'     => $id,
                '_score'  => $hit['text_match'] ?? 1,
                '_source' => $doc,
            ];
        }, $typesenseHits);
    }

    /**
     * Convert Typesense facet_counts to the ES aggregations shape the frontend expects:
     * { fieldName: { buckets: [{ key, doc_count }] } }
     */
    private function mapFacetsToAggregations(array $facetCounts): array
    {
        $aggs = [];
        foreach ($facetCounts as $facet) {
            $aggs[$facet['field_name']] = [
                'buckets' => array_map(fn ($c) => [
                    'key'       => $c['value'],
                    'doc_count' => $c['count'],
                ], $facet['counts'] ?? []),
            ];
        }
        return $aggs;
    }

    /**
     * Build a Typesense filter_by string from V2 top-level pipe-delimited params.
     * Only known facet fields are forwarded to avoid leaking pagination/sort into the filter.
     */
    private function buildFilterBy(string $type, array $params): string
    {
        $fields  = self::TYPESENSE_FILTERABLE_MAP[$type] ?? [];
        $clauses = [];

        foreach ($fields as $field) {
            if (empty($params[$field])) {
                continue;
            }

            $values = array_filter(array_map('trim', explode('|', (string) $params[$field])));
            if (empty($values)) {
                continue;
            }

            $quoted    = array_map(fn ($v) => '`' . str_replace('`', '', $v) . '`', $values);
            $clauses[] = $field . ':=[' . implode(',', $quoted) . ']';
        }

        return implode(' && ', $clauses);
    }

    private function sort(array $hits, string $type, string $sortParam): array
    {
        $parts     = explode(':', $sortParam, 2);
        $rawField  = $parts[0];
        $direction = $parts[1] ?? 'desc';

        $field = ($type === 'datasets' && $rawField === 'title') ? 'shortTitle' : $rawField;

        if ($field === 'score') {
            return $direction === 'desc' ? $hits : array_reverse($hits);
        }

        usort($hits, function ($a, $b) use ($field, $direction) {
            $aVal = $a['_source'][$field] ?? null;
            $bVal = $b['_source'][$field] ?? null;

            if (is_string($aVal) && strtotime($aVal) !== false) {
                $cmp = strtotime((string)$aVal) <=> strtotime((string)$bVal);
            } elseif (is_string($aVal)) {
                $cmp = strtoupper((string)$aVal) <=> strtoupper((string)$bVal);
            } else {
                $cmp = $aVal <=> $bVal;
            }

            return $direction === 'asc' ? $cmp : -$cmp;
        });

        return $hits;
    }

    private function hydrate(array $hits, string $type, string $viewType = 'full'): array
    {
        return match ($type) {
            'datasets'                => (new DatasetHydrator())->hydrate($hits, $viewType),
            'tools'                   => (new ToolHydrator())->hydrate($hits),
            'collections'             => (new CollectionHydrator())->hydrate($hits),
            'dur'                     => (new DataUseHydrator())->hydrate($hits),
            'publications'            => (new PublicationHydrator())->hydrate($hits),
            'data_custodian_networks' => (new DataCustodianNetworkHydrator())->hydrate($hits),
            'data_custodians'         => (new DataCustodianHydrator())->hydrate($hits),
            default                   => $hits,
        };
    }
}
