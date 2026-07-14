<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * gwdm30_project_grants — one row per ProjectGrant entry.
 *
 * The schema defines projectGrantStartDate / projectGrantEndDate as plain
 * strings (not constrained date formats), so they are stored as varchar
 * rather than DATE to avoid silent truncation of non-ISO values.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('gwdm30_project_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            $table->string('pid', 150)->nullable();
            $table->string('project_grant_name', 150)->nullable();
            $table->string('lead_researcher', 150)->nullable();
            $table->string('lead_research_institute', 150)->nullable();
            $table->string('grant_number')->nullable();
            $table->string('project_grant_start_date')->nullable();
            $table->string('project_grant_end_date')->nullable();
            $table->string('project_grant_scope', 500)->nullable();
            $table->timestamps();

            $table->index('dataset_version_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gwdm30_project_grants');
    }
};
