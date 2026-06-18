<?php

namespace App\Services\Gwdm;

use App\Models\Dataset;
use App\Models\DatasetVersion;

/**
 * Handler for GWDM 3.0 — STUB.
 *
 * Extends Gwdm2xHandler because the 3.0 metadata schema is a superset of
 * 2.x: the required block and publisher field formats are identical. Only
 * the persistence strategy differs — structured SQL tables replace (or
 * supplement) the JSON metadata blob.
 *
 * To activate:
 *   1. Uncomment and run migration 2026_06_17_000003_create_gwdm30_structured_tables.php
 *   2. Implement Gwdm30PersistenceService
 *   3. Uncomment the afterStore() / afterRead() bodies below
 */
class Gwdm30Handler extends Gwdm2xHandler
{
    /**
     * Called after the DatasetVersion row is created/updated.
     * Writes structured GWDM 3.0 fields to dedicated SQL tables.
     */
    public function afterStore(Dataset $dataset, DatasetVersion $dv, array $gwdm): void
    {
        // app(\App\Services\Gwdm\Gwdm30PersistenceService::class)->persist($dv, $gwdm);
    }

    /**
     * Called when reading a version for a response.
     * Returns supplementary data from structured SQL tables to merge
     * back into the GWDM envelope.
     */
    public function afterRead(DatasetVersion $dv): array
    {
        // return app(\App\Services\Gwdm\Gwdm30PersistenceService::class)->read($dv);
        return [];
    }

    /**
     * Write linkage data to dataset_version_gwdm30_linkages instead of the
     * 2.x junction table. Implementation pending GWDM 3.0 schema finalisation.
     */
    public function extractLinkages(DatasetVersion $dv): void
    {
        // app(\App\Services\Gwdm\Gwdm30PersistenceService::class)->extractLinkages($dv);
    }
}
