<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `patch` column to dataset_versions and makes `metadata` nullable.
 *
 * Delta versioning strategy:
 *  - Version 1 (base snapshot) : metadata = full JSON envelope, patch = null
 *  - Version N>1 (delta)       : metadata = reduced envelope {gwdmVersion, original_metadata},
 *                                patch    = RFC 6902 JSON Patch array of changes to the GWDM object
 *  - Every 10th version        : treated as a materialised snapshot — metadata = full JSON
 *                                envelope, patch = null — to cap reconstruction cost at ≤9 deltas.
 *
 * A null `patch` column is therefore the reliable indicator that a row is a full snapshot
 * (base or materialised). Code that reconstructs a specific version always seeks the nearest
 * snapshot at-or-below the target version, then walks forward applying deltas only from that
 * point.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            // Nullable so base/snapshot rows carry no patch overhead.
            $table->json('patch')->nullable()->after('metadata');

            // Must be nullable now that delta rows store only a reduced envelope
            // (no metadata.metadata key), not the full GWDM snapshot.
            $table->json('metadata')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->dropColumn('patch');
            $table->json('metadata')->nullable(false)->change();
        });
    }
};
