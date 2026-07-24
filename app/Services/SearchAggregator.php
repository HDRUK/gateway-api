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

    public function searchImmediate(string $query, string $type, array $params = [], array $only = []): array
    {
        $results = [];

        foreach ($this->providers as $provider) {
            if ($provider->isDeferred()) {
                continue;
            }

            if (!empty($only) && !in_array($provider->getShortName(), $only)) {
                continue;
            }

            if (!in_array($type, $provider->getSupportedTypes())) {
                continue;
            }

            try {
                $providerResult = $provider->search($query, $type, $params);

                $results[$provider->getShortName()] = [
                    'source'        => $providerResult['source'] ?? null,
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

        return $results;
    }

    /** @return SearchProvider[] */
    public function getDeferredProviders(string $type, array $only = []): array
    {
        return array_values(array_filter(
            $this->providers,
            fn ($p) => $p->isDeferred()
                && in_array($type, $p->getSupportedTypes())
                && (empty($only) || in_array($p->getShortName(), $only))
        ));
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
                    'source'        => $providerResult['source'] ?? null,
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
