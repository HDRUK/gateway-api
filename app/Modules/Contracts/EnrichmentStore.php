<?php

namespace App\Modules\Contracts;

use App\Modules\EnrichmentContext;
use App\Modules\EnrichmentResult;

/**
 * Where enrichment results are kept between a queued pull and later reads,
 * keyed by module, subject and params.
 */
interface EnrichmentStore
{
    public function find(string $module, EnrichmentContext $context): ?EnrichmentResult;

    public function put(EnrichmentContext $context, EnrichmentResult $result): void;
}
