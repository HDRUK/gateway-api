<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Record the schema each dataset version was submitted in - its ingestion
 * format, e.g. HDRUK 4.1.0 or CRUK 1.0.0 - alongside the GWDM version it is
 * stored as.
 *
 * Until now the declared input schema was passed to TRASER and discarded.
 * Drafts need it: they are translated without validation and re-edited from
 * original_metadata, so the editor must know what format that submission is in.
 *
 * Both columns are nullable: existing rows, and submissions that declare
 * nothing, have no known source schema. GMI rows record HDRUK with a null
 * version (TRASER detects the version but does not report it).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->string('source_schema_model', 50)->nullable()->after('gwdm_version');
            $table->string('source_schema_version', 20)->nullable()->after('source_schema_model');
        });
    }

    public function down(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->dropColumn(['source_schema_model', 'source_schema_version']);
        });
    }
};
