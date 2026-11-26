<?php

namespace App\Modules\Stores;

use App\Modules\Contracts\EnrichmentStore;
use App\Modules\EnrichmentContext;
use App\Modules\EnrichmentResult;

/**
 * Stores nothing. Bound until the database store exists (module_enrichments),
 * so every read reports Missing and every pull runs in full.
 */
class NullEnrichmentStore implements EnrichmentStore
{
    public function find(string $module, EnrichmentContext $context): ?EnrichmentResult
    {
        return null;
    }

    public function put(EnrichmentContext $context, EnrichmentResult $result): void
    {
    }
}
