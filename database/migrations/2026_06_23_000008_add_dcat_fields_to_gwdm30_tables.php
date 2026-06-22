<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add DCAT 3 / HealthDCAT-AP alignment fields to GWDM 3.0 structured tables.
 *
 * Also creates two new entity tables:
 *   gwdm30_distributions     — dcat:Distribution (physical/accessible forms of a dataset)
 *   gwdm30_quality_annotations — dqv:QualityAnnotation (DUF scores + external certifications)
 *
 * The typical_age_range free-text column is replaced by the typed
 * min_typical_age / max_typical_age integer columns (healthdcatap:minTypicalAge /
 * healthdcatap:maxTypicalAge) to enable clean RDF serialisation.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── gwdm30_summary ────────────────────────────────────────────────────
        Schema::table('gwdm30_summary', function (Blueprint $table) {
            $table->string('license_url', 512)->nullable()->after('doi_name');      // dct:license
            $table->string('landing_page', 512)->nullable()->after('license_url');  // dcat:landingPage
            $table->string('creator_name', 256)->nullable()->after('landing_page'); // dct:creator → name
            $table->string('creator_ror_id', 256)->nullable()->after('creator_name');
            $table->string('creator_orcid_id', 256)->nullable()->after('creator_ror_id');
            $table->string('creator_gateway_id', 256)->nullable()->after('creator_orcid_id');
            $table->json('theme')->nullable()->after('creator_gateway_id');          // dcat:theme (URI array)
        });

        // ── gwdm30_coverage ───────────────────────────────────────────────────
        Schema::table('gwdm30_coverage', function (Blueprint $table) {
            $table->dropColumn('typical_age_range');
            $table->unsignedInteger('min_typical_age')->nullable()->after('spatial');   // healthdcatap:minTypicalAge
            $table->unsignedInteger('max_typical_age')->nullable()->after('min_typical_age'); // healthdcatap:maxTypicalAge
            $table->text('population_coverage')->nullable()->after('max_typical_age');  // healthdcatap:populationCoverage
            $table->unsignedBigInteger('number_of_unique_individuals')->nullable()->after('population_coverage'); // healthdcatap:numberOfUniqueIndividuals
            $table->unsignedBigInteger('number_of_records')->nullable()->after('number_of_unique_individuals');   // healthdcatap:numberOfRecords
        });

        // ── gwdm30_provenance ─────────────────────────────────────────────────
        Schema::table('gwdm30_provenance', function (Blueprint $table) {
            $table->date('retention_period_start')->nullable()->after('temporal_distribution_release_date'); // healthdcatap:retentionPeriod
            $table->date('retention_period_end')->nullable()->after('retention_period_start');
        });

        // ── gwdm30_accessibility ──────────────────────────────────────────────
        Schema::table('gwdm30_accessibility', function (Blueprint $table) {
            $table->string('legal_basis', 512)->nullable()->after('data_processor');      // dpv:hasLegalBasis
            $table->string('personal_data', 64)->nullable()->after('legal_basis');        // dpv:hasPersonalData
            $table->text('applicable_legislation')->nullable()->after('personal_data');   // dcatap:applicableLegislation
        });

        // ── gwdm30_distributions (new) ────────────────────────────────────────
        Schema::create('gwdm30_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            $table->string('title', 256)->nullable();          // dct:title
            $table->text('description')->nullable();           // dct:description
            $table->string('access_url', 1024)->nullable();    // dcat:accessURL
            $table->string('download_url', 1024)->nullable();  // dcat:downloadURL
            $table->string('media_type', 256)->nullable();     // dcat:mediaType
            $table->string('format', 128)->nullable();         // dct:format
            $table->unsignedBigInteger('byte_size')->nullable(); // dcat:byteSize
            $table->string('license_url', 512)->nullable();    // dct:license (distribution-level)
            $table->string('access_service', 1024)->nullable(); // dcat:accessService
            $table->date('issued')->nullable();                 // dct:issued
            $table->date('modified')->nullable();               // dct:modified
            $table->timestamps();

            $table->index('dataset_version_id');
        });

        // ── gwdm30_quality_annotations (new) ──────────────────────────────────
        Schema::create('gwdm30_quality_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            $table->enum('annotation_type', ['duf_score', 'certification'])->nullable(); // dqv:inDimension classifier
            $table->string('quality_dimension', 128)->nullable(); // dqv:inDimension (e.g. completeness, accuracy)
            $table->string('quality_value', 64)->nullable();      // dqv:value
            $table->text('quality_description')->nullable();      // skos:note
            $table->string('certification_url', 1024)->nullable(); // link to certification document
            $table->date('annotation_date')->nullable();           // prov:generatedAtTime
            $table->timestamps();

            $table->index('dataset_version_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gwdm30_quality_annotations');
        Schema::dropIfExists('gwdm30_distributions');

        Schema::table('gwdm30_accessibility', function (Blueprint $table) {
            $table->dropColumn(['legal_basis', 'personal_data', 'applicable_legislation']);
        });

        Schema::table('gwdm30_provenance', function (Blueprint $table) {
            $table->dropColumn(['retention_period_start', 'retention_period_end']);
        });

        Schema::table('gwdm30_coverage', function (Blueprint $table) {
            $table->dropColumn(['min_typical_age', 'max_typical_age', 'population_coverage', 'number_of_unique_individuals', 'number_of_records']);
            $table->string('typical_age_range', 50)->nullable()->after('spatial');
        });

        Schema::table('gwdm30_summary', function (Blueprint $table) {
            $table->dropColumn(['license_url', 'landing_page', 'creator_name', 'creator_ror_id', 'creator_orcid_id', 'creator_gateway_id', 'theme']);
        });
    }
};
