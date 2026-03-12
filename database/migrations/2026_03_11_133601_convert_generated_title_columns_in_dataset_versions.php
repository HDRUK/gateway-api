<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converts `title` and `short_title` from MySQL STORED GENERATED columns to
 * regular (application-managed) columns.
 *
 * Why this is required for delta versioning
 * -----------------------------------------
 * The generated expressions extract values directly from the `metadata` JSON:
 *
 *   JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.summary.title'))
 *
 * Delta version rows intentionally store only a reduced metadata envelope
 * (gwdmVersion + original_metadata) — the full GWDM object lives in `patch`.
 * With no `metadata.metadata` key present, the generated expressions evaluate
 * to NULL, making title-based search (`scopeFilterTitle`) fail for any dataset
 * whose latest version is a delta (i.e. almost all datasets after the first update).
 *
 * The fix: populate both columns in PHP at write time, using the reconstructed
 * full GWDM metadata. All existing rows already hold correct values — converting
 * from GENERATED to regular preserves the data in MySQL.
 *
 * SQLite (test environment)
 * -------------------------
 * The previous migrations that added these columns used a MySQL guard and fell
 * back to plain nullable columns for SQLite, so those environments are already
 * in the target state and no DDL changes are needed here.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // MySQL: MODIFY COLUMN strips the GENERATED attribute while retaining
        // existing values and the collation. We also re-create the indexes
        // because they are implicitly dropped when the column definition changes.
        DB::statement(
            "ALTER TABLE dataset_versions
            DROP INDEX idx_title,
            DROP INDEX dataset_versions_short_title_index,
            MODIFY COLUMN title        VARCHAR(500) NULL COLLATE utf8mb4_general_ci,
            MODIFY COLUMN short_title  VARCHAR(255) NULL COLLATE utf8mb4_general_ci"
        );

        DB::statement('ALTER TABLE dataset_versions ADD INDEX idx_title (title(500))');
        DB::statement('ALTER TABLE dataset_versions ADD INDEX idx_short_title (short_title)');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Restore the STORED GENERATED expressions.
        // Note: any delta-version rows written after the `up()` migration was
        // run will have had their title/short_title set to a plain string value.
        // Rolling back here restores the expressions but those rows' values
        // will be derived from their (reduced) metadata JSON, which may evaluate
        // to NULL until the row is updated with a full snapshot. This rollback
        // path is provided for completeness but is considered destructive in a
        // production environment that has already processed delta versions.
        DB::statement(
            "ALTER TABLE dataset_versions
            DROP INDEX idx_title,
            DROP INDEX idx_short_title,
            MODIFY COLUMN title VARCHAR(500) NULL
                GENERATED ALWAYS AS (
                    CASE WHEN LEFT(metadata, 1) = '{'
                        THEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.summary.title'))
                        ELSE JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.title'))
                    END
                ) STORED COLLATE utf8mb4_general_ci,
            MODIFY COLUMN short_title VARCHAR(255)
                GENERATED ALWAYS AS (
                    CASE WHEN LEFT(metadata, 1) = '{'
                        THEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.metadata.summary.shortTitle'))
                        ELSE JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.shortTitle'))
                    END
                ) STORED COLLATE utf8mb4_general_ci"
        );

        DB::statement('ALTER TABLE dataset_versions ADD INDEX idx_title (title(500))');
        DB::statement('ALTER TABLE dataset_versions ADD INDEX idx_short_title (short_title)');
    }
};
