<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        // Data back-fill of existing rows from metadata->gwdmVersion is handled by a
        // deployment step (database/deployment-steps/*_backfill_gwdm_version.php),
        // which runs via `php artisan deploy:run` after migrations. New rows get the
        // '2.0' column default until the service layer sets an explicit version.
    }

    public function down(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->dropIndex(['gwdm_version']);
            $table->dropColumn('gwdm_version');
        });
    }
};
