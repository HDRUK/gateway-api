<?php

namespace Tests\Fakes\Modules;

use App\Modules\Contracts\EnrichmentModule;
use App\Modules\EnrichmentContext;
use App\Modules\EnrichmentResult;
use Throwable;

/**
 * A module whose behaviour each test sets directly. Bind an instance with
 * $this->app->instance(FakeModule::class, $fake) so the manager uses it.
 */
class FakeModule implements EnrichmentModule
{
    public bool $supports = true;

    public ?string $version = '1';

    public array $payload = ['hello' => 'world'];

    public ?Throwable $throws = null;

    public int $enrichCalls = 0;

    public int $versionCalls = 0;

    /** @var EnrichmentContext[] */
    public array $contexts = [];

    public function supports(EnrichmentContext $context): bool
    {
        return $this->supports;
    }

    public function sourceVersion(EnrichmentContext $context): ?string
    {
        $this->versionCalls++;

        return $this->version;
    }

    public function enrich(EnrichmentContext $context): EnrichmentResult
    {
        $this->enrichCalls++;
        $this->contexts[] = $context;

        if ($this->throws) {
            throw $this->throws;
        }

        return EnrichmentResult::ok($this->payload, $this->version);
    }
}
