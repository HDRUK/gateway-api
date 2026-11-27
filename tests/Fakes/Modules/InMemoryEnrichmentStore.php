<?php

namespace Tests\Fakes\Modules;

use App\Modules\Contracts\EnrichmentStore;
use App\Modules\EnrichmentContext;
use App\Modules\EnrichmentResult;

class InMemoryEnrichmentStore implements EnrichmentStore
{
    /** @var array<string, EnrichmentResult> */
    public array $results = [];

    public function find(string $module, EnrichmentContext $context): ?EnrichmentResult
    {
        return $this->results[$this->key($module, $context)] ?? null;
    }

    public function put(EnrichmentContext $context, EnrichmentResult $result): void
    {
        $this->results[$this->key($result->module, $context)] = $result;
    }

    private function key(string $module, EnrichmentContext $context): string
    {
        return implode('|', [$module, $context->subjectType, $context->subjectId, $context->paramsHash()]);
    }
}
