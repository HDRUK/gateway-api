<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * gwdm30_tissues_sample_collections — one row per TissuesSampleCollection entry.
 *
 * All CommaSeparatedValues fields are stored as plain strings.
 * The nested tissueSampleMetadata sub-object is flattened into columns prefixed
 * `tsm_*` to avoid a further child table for a relatively shallow object.
 * The sampleDonor sub-object within TissueSampleMetadata is also flattened
 * with prefix `tsm_donor_*`.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('gwdm30_tissues_sample_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();

            // TissuesSampleCollection top-level fields
            $table->string('collection_id', 100)->nullable();
            $table->string('data_categories')->nullable();
            $table->string('material_type')->nullable();
            $table->string('access_conditions')->nullable();
            $table->string('collection_type')->nullable();
            $table->string('disease')->nullable();
            $table->string('storage_temperature')->nullable();
            $table->string('sample_age_range')->nullable();

            // TissueSampleMetadata sub-object (flattened)
            $table->string('tsm_id', 50)->nullable();
            $table->string('tsm_sample_type')->nullable();
            $table->string('tsm_storage_temperature')->nullable();
            $table->date('tsm_creation_date')->nullable();
            $table->string('tsm_anatomical_site_ontology_code')->nullable();
            $table->string('tsm_anatomical_site_ontology_description')->nullable();
            $table->string('tsm_anatomical_site_free_text')->nullable();
            $table->string('tsm_sample_content_diagnosis')->nullable();
            $table->string('tsm_use_restrictions')->nullable();

            // SampleDonor sub-object (flattened under tsm)
            $table->string('tsm_donor_id', 50)->nullable();
            $table->string('tsm_donor_sex')->nullable();
            $table->date('tsm_donor_birth_date')->nullable();
            $table->string('tsm_donor_data_categories')->nullable();

            $table->timestamps();

            $table->index('dataset_version_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gwdm30_tissues_sample_collections');
    }
};
