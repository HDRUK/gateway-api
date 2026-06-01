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

    public function search(string $query, string $type, array $params = []): array
    {
        try {
            $filterConfig = self::FILTER_TYPE_MAP[$type] ?? ['type' => $type, 'enabledOnly' => false];

            $input          = $params;
            $input['query'] = $query;
            $input['aggs']  = FilterCache::get($filterConfig['type'], $filterConfig['enabledOnly']);

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

            return [
                'hits'         => $hydrated,
                'total'        => $total,
                'aggregations' => $aggs,
                'ids'          => $ids,
            ];
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
