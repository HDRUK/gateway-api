<?php

namespace App\Modules\Contracts;

use App\Modules\EnrichmentContext;
use App\Modules\EnrichmentResult;

/**
 * A source of extra data for a subject (e.g. CLOSER for a PRUK dataset).
 *
 * The module's name, partners and subject types are set in config/modules.php,
 * not here, so the same class can be registered more than once if needed.
 * The manager catches anything a module throws.
 */
interface EnrichmentModule
{
    /**
     * Whether this subject can be enriched at all (e.g. it carries a CLOSER
     * identifier). Called on reads and saves, so it must be cheap: a local
     * database read at most, never a call to the external source.
     */
    public function supports(EnrichmentContext $context): bool;

    /**
     * The source's current version of the data for this subject, used to skip
     * downloads that would change nothing. Null means unknown: always download.
     * Should be a cheap call to the source.
     */
    public function sourceVersion(EnrichmentContext $context): ?string;

    /** Fetch the data. Only ever called from a queued job. */
    public function enrich(EnrichmentContext $context): EnrichmentResult;
}
