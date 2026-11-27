<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Harvested CLOSER Discovery data (GAT-9844): one row per wave (StudyUnit),
 * its datasets (PhysicalInstance) and its variables with their statistics.
 *
 * Frequencies are raw counts. They must be rounded and small counts
 * suppressed before anything shows them.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('closer_waves', function (Blueprint $table) {
            $table->id();
            $table->string('agency', 64);
            $table->string('identifier', 64);
            $table->unsignedInteger('version')->nullable();
            $table->string('title')->nullable();
            $table->string('status', 16)->default('pending');
            $table->unsignedInteger('dataset_count')->default(0);
            $table->unsignedInteger('variable_count')->default(0);
            $table->timestamp('harvested_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['agency', 'identifier']);
        });

        Schema::create('closer_datasets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('closer_wave_id')->constrained('closer_waves')->cascadeOnDelete();
            $table->string('identifier', 64);
            $table->unsignedInteger('version')->nullable();
            $table->string('title')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedInteger('case_quantity')->nullable();
            $table->timestamps();

            $table->unique(['closer_wave_id', 'identifier']);
        });

        Schema::create('closer_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('closer_wave_id')->constrained('closer_waves')->cascadeOnDelete();
            $table->foreignId('closer_dataset_id')->nullable()->constrained('closer_datasets')->nullOnDelete();
            $table->string('identifier', 64);
            $table->unsignedInteger('version')->nullable();
            $table->string('name')->nullable();
            $table->text('label')->nullable();
            $table->string('question_identifier', 64)->nullable();
            $table->string('data_type', 32)->nullable();
            $table->json('summary_statistics')->nullable();
            $table->json('frequencies')->nullable();
            $table->timestamps();

            $table->unique(['closer_wave_id', 'identifier']);
            $table->index(['closer_wave_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closer_variables');
        Schema::dropIfExists('closer_datasets');
        Schema::dropIfExists('closer_waves');
    }
};
