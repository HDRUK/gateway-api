<?php

namespace App\Services\Gwdm;

use App\Models\Dataset;
use App\Models\DatasetVersion;

/**
 * Handler for GWDM 3.0.
 *
 * Extends Gwdm2xHandler because the 3.0 metadata schema is a superset of
 * 2.x: the required block, publisher field format, and linkage extraction
 * are all identical. Only the persistence strategy differs — every GWDM
 * section is written to dedicated SQL tables rather than solely to the
 * JSON metadata blob.
 *
 * extractLinkages() is inherited from Gwdm2xHandler unchanged — both 2.x
 * and 3.0 write dataset linkages to dataset_version_has_dataset_version.
 */
class Gwdm30Handler extends Gwdm2xHandler
{
    public function afterStore(Dataset $dataset, DatasetVersion $dv, array $gwdm): void
    {
        app(Gwdm30PersistenceService::class)->persist($dv, $gwdm);
    }

    public function afterRead(DatasetVersion $dv): array
    {
        return app(Gwdm30PersistenceService::class)->read($dv);
    }
}
