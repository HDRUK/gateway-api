<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class DeferredProviderSearch implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries   = 2;
    public $timeout = 30;

    public function __construct(
        private string $providerClass,
        private string $token,
        private string $query,
        private string $type,
        private array  $params,
    ) {
        $this->onQueue('high');
    }

    public function handle(): void
    {
        $provider = app($this->providerClass);

        try {
            $result = $provider->search($this->query, $this->type, $this->params);
        } catch (\Throwable $e) {
            \Log::error("Deferred search provider {$this->providerClass} failed", [
                'error' => $e->getMessage(),
            ]);
            $result = ['hits' => [], 'total' => 0, 'aggregations' => [], 'ids' => []];
        }

        Cache::put(
            "search:deferred:{$this->token}:result:{$provider->getShortName()}",
            [
                'source'        => $result['source'] ?? null,
                'provider_logo' => $provider->getProviderLogo(),
                'about'         => $provider->getProviderBlurb(),
                'hits'          => $result['hits'],
                'total'         => $result['total'],
                'aggregations'  => $result['aggregations'] ?? [],
                'ids'           => $result['ids'] ?? [],
            ],
            config('gateway.search_deferred_ttl')
        );
    }

    public function tags(): array
    {
        return [
            'deferred_provider_search',
            'provider:' . class_basename($this->providerClass),
            'token:' . $this->token,
        ];
    }
}
