<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add a queryable gwdm_version column to dataset_versions.
 *
 * Previously the GWDM version was only stored inside the JSON metadata envelope
 * (metadata->gwdmVersion), which is unindexed and NULL on delta rows (where
 * metadata stores only {gwdmVersion, original_metadata} without the full GWDM).
 *
 * This column is the authoritative discriminator for:
 *   - Which GWDM schema version a row was written under
 *   - Selecting the latest version for a specific schema context
 *     (e.g. "give me the most recent GWDM 2.1 row for dataset 42")
 *   - Future GWDM 3.0 rows that use structured SQL columns instead of JSON
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->string('gwdm_version', 20)->default('2.0')->after('version');
            $table->index('gwdm_version');
        });

        // Back-fill from the JSON envelope.
        //
        // Snapshot rows (patch IS NULL) have gwdmVersion set in metadata->gwdmVersion.
        // Delta rows (patch IS NOT NULL) store a reduced envelope where metadata = []
        // or metadata = {gwdmVersion, original_metadata} — JSON_EXTRACT may return NULL.
        //
        // COALESCE falls back to '2.0' for any row where the JSON path is absent
        // (delta rows, legacy rows, or rows where metadata is a bare empty array).
        // This is always correct because no GWDM 2.1+ data exists in the DB today.
        //
        // Guarded to MySQL only — JSON_UNQUOTE/JSON_EXTRACT are not available in
        // SQLite (used by the test suite). The column default of '2.0' covers
        // test fixtures correctly.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("
                UPDATE dataset_versions
                SET gwdm_version = COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(NULLIF(metadata, 'null'), '$.gwdmVersion')),
                    '2.0'
                )
                WHERE deleted_at IS NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->dropIndex(['gwdm_version']);
            $table->dropColumn('gwdm_version');
        });
    }
};
