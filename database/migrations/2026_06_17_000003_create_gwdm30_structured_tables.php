<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GWDM 3.0 structured SQL tables — SKELETON ONLY.
 *
 * All content is commented out. Do not run this migration until the GWDM 3.0
 * schema is finalised and Gwdm30PersistenceService is implemented.
 *
 * Design intent:
 *   - These tables store structured fields extracted from GWDM 3.0 metadata,
 *     replacing the JSON-only storage used by GWDM 2.x.
 *   - Only rows with gwdm_version = '3.0' in dataset_versions use these tables.
 *   - The gwdm_version column on dataset_versions is the discriminator.
 *   - dataset_version_gwdm30_linkages replaces the dual-source problem between
 *     dataset_version_has_dataset_version (gateway-tracked) and the JSON
 *     metadata.linkage.datasetLinkage field (free-text). 3.0 uses one table only.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Schema::create('dataset_version_gwdm30_linkages', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('dataset_version_id')
        //         ->constrained('dataset_versions')
        //         ->cascadeOnDelete();
        //     // linkedDatasets | isDerivedFrom | isPartOf | isMemberOf
        //     $table->string('linkage_type');
        //     // Null for external datasets not registered in the gateway
        //     $table->foreignId('target_dataset_id')
        //         ->nullable()
        //         ->constrained('datasets')
        //         ->nullOnDelete();
        //     $table->string('target_title')->nullable();
        //     $table->string('target_url')->nullable();
        //     $table->boolean('is_external')->default(false);
        //     $table->timestamps();
        //
        //     $table->index(['dataset_version_id', 'linkage_type']);
        // });

        // Schema::create('dataset_version_gwdm30_accessibility', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('dataset_version_id')
        //         ->constrained('dataset_versions')
        //         ->cascadeOnDelete();
        //     // Previously JSON-extracted in FormHydrationController::getDefaultValues()
        //     // and indexed via DEFAULTS_PATHS['2.0']
        //     $table->json('data_use_limitation')->nullable();
        //     $table->json('data_use_requirements')->nullable();
        //     $table->string('access_rights')->nullable();
        //     $table->text('access_service')->nullable();
        //     $table->string('access_request_cost')->nullable();
        //     $table->string('delivery_lead_time')->nullable();
        //     $table->json('formats')->nullable();
        //     $table->unique('dataset_version_id');
        //     $table->timestamps();
        // });

        // Additional candidates (add when 3.0 schema is confirmed):
        // dataset_version_gwdm30_provenance — issued, modified, accrual_periodicity
        // dataset_version_gwdm30_coverage   — population_size, geographic coverage
    }

    public function down(): void
    {
        // Schema::dropIfExists('dataset_version_gwdm30_accessibility');
        // Schema::dropIfExists('dataset_version_gwdm30_linkages');
    }
};
