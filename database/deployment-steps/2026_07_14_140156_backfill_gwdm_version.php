<?php

use App\DeploymentSteps\DeploymentStep;
use Illuminate\Support\Facades\DB;

/**
 * Back-fill dataset_versions.gwdm_version from the JSON metadata envelope.
 *
 * The column (migration 2026_06_17_000001) defaults to '2.0', which is correct for
 * new rows but wrong for legacy rows written under GWDM 1.x / 2.0 — their real
 * version lives in metadata->gwdmVersion. This step copies it across so version
 * resolution (handler selection, snapshot-on-schema-change, backfill source
 * selection) reads the true schema version for existing data.
 *
 * COALESCE falls back to '2.0' where the JSON path is absent (delta rows store a
 * reduced envelope, legacy rows may have a bare/empty metadata array).
 *
 * MySQL-only: JSON_UNQUOTE/JSON_EXTRACT are unavailable in SQLite (the test DB),
 * where the '2.0' column default already covers fixtures — so this is a no-op there.
 */
return new class () extends DeploymentStep {
    public function handle(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->info('Skipping gwdm_version back-fill: connection is not MySQL.');

            return;
        }

        $affected = DB::update("
            UPDATE dataset_versions
            SET gwdm_version = COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(NULLIF(metadata, 'null'), '$.gwdmVersion')),
                '2.0'
            )
            WHERE deleted_at IS NULL
        ");

        $this->info("Back-filled gwdm_version on {$affected} dataset_versions row(s).");
    }
};
