<?php

namespace App\Services;

use App\Contracts\SearchProvider;

class SearchAggregator
{
    /** @var SearchProvider[] */
    protected array $providers = [];

    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    public function search(string $query, string $type, array $params = []): array
    {
        $results = [];

        foreach ($this->providers as $provider) {
            if (!in_array($type, $provider->getSupportedTypes())) {
                continue;
            }

            try {
                $providerResult = $provider->search($query, $type, $params);

                $results[$provider->getShortName()] = [
                    'provider_logo' => $provider->getProviderLogo(),
                    'about'         => $provider->getProviderBlurb(),
                    'hits'          => $providerResult['hits'],
                    'total'         => $providerResult['total'],
                    'aggregations'  => $providerResult['aggregations'] ?? [],
                    'ids'           => $providerResult['ids'] ?? [],
                ];
            } catch (\Throwable $e) {
                \Log::error("Search provider {$provider->getShortName()} failed", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($results)) {
            return [
                'message' => 'failed',
                'data'    => null,
            ];
        }

        return [
            'message' => 'success',
            'data' => [
                'query'   => $query,
                'type'    => $type,
                'results' => $results,
            ],
        ];
    }
}
