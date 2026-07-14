<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create all dedicated SQL tables for GWDM 3.0 structured persistence.
 *
 * GWDM 3.0 stores every metadata field in typed relational columns rather than
 * a JSON blob. Each section maps to a dedicated table (one row per dataset version,
 * except observations which is one-to-many). Dataset and publication linkages
 * continue to use the existing junction tables — see 2026_06_18_000001.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('gwdm30_accessibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            // access sub-section
            $table->string('access_rights')->nullable();
            $table->text('access_service')->nullable();
            $table->string('access_request_cost')->nullable();
            $table->string('delivery_lead_time')->nullable();
            $table->string('jurisdiction', 100)->nullable();
            $table->text('data_controller')->nullable();
            $table->text('data_processor')->nullable();
            // usage sub-section
            $table->json('data_use_limitation')->nullable();
            $table->json('data_use_requirements')->nullable();
            $table->string('resource_creator')->nullable();
            // formatAndStandards sub-section
            $table->string('vocabulary_encoding_schemes')->nullable();
            $table->string('conforms_to')->nullable();
            $table->string('languages')->nullable();
            $table->json('formats')->nullable();
            $table->unique('dataset_version_id');
            $table->timestamps();
        });

        Schema::create('gwdm30_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            // title and shortTitle are already indexed in dataset_versions.title / short_title
            $table->text('abstract')->nullable();
            $table->string('contact_point')->nullable();
            $table->text('keywords')->nullable();
            $table->json('controlled_keywords')->nullable();
            $table->string('dataset_type', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('doi_name', 512)->nullable();
            $table->string('publisher_name')->nullable();
            $table->string('publisher_gateway_id')->nullable();
            $table->integer('population_size')->nullable();
            $table->unique('dataset_version_id');
            $table->timestamps();
        });

        Schema::create('gwdm30_coverage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            $table->text('spatial')->nullable();
            $table->string('typical_age_range', 50)->nullable();
            $table->string('pathway')->nullable();
            $table->string('followup', 100)->nullable();
            $table->unique('dataset_version_id');
            $table->timestamps();
        });

        Schema::create('gwdm30_provenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            // origin sub-section
            $table->string('origin_purpose')->nullable();
            $table->string('origin_source')->nullable();
            $table->string('origin_collection_situation')->nullable();
            // temporal sub-section
            $table->date('temporal_start_date')->nullable();
            $table->date('temporal_end_date')->nullable();
            $table->string('temporal_time_lag', 100)->nullable();
            $table->string('temporal_accrual_periodicity', 100)->nullable();
            $table->unique('dataset_version_id');
            $table->timestamps();
        });

        Schema::create('gwdm30_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            $table->string('observed_node');
            $table->decimal('measured_value', 15, 4)->nullable();
            $table->date('observation_date')->nullable();
            $table->string('measured_property')->nullable();
            $table->text('disambiguating_description')->nullable();
            $table->timestamps();

            $table->index(['dataset_version_id', 'observed_node']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gwdm30_observations');
        Schema::dropIfExists('gwdm30_provenance');
        Schema::dropIfExists('gwdm30_coverage');
        Schema::dropIfExists('gwdm30_summary');
        Schema::dropIfExists('gwdm30_accessibility');
    }
};
