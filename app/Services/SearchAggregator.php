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

    // Intentionally not exposed via swagger for the time being
    public function search(string $query): array
    {
        $results = [];

        foreach ($this->providers as $provider) {
            try {
                $results[$provider->getShortName()] = [
                    'provider_logo' => $provider->getProviderLogo(),
                    'about' => $provider->getProviderBlurb(),
                    $provider->search($query),
                ];
            } catch (\Throwable $e) {
                \Log::error("Search provider {$provider->getShortName()} failed with error ", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'message' => 'success',
            'data' => [
                'query' => $query,
                'results' => $results,
            ],
        ];
    }
}
