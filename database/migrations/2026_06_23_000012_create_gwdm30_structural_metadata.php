<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Three-level hierarchy for GWDM 3.0 `structuralMetadata`:
 *   gwdm30_structural_tables  (DataTable)
 *   gwdm30_structural_columns (DataColumn  → FK to table)
 *   gwdm30_structural_values  (DataValue   → FK to column)
 *
 * Cascading deletes mean that removing all tables for a dataset_version_id
 * automatically removes the related columns and values.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('gwdm30_structural_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            $table->string('name', 500)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('dataset_version_id');
        });

        Schema::create('gwdm30_structural_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gwdm30_structural_table_id')
                ->constrained('gwdm30_structural_tables')
                ->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('data_type')->nullable();
            $table->text('description')->nullable();
            $table->boolean('sensitive')->default(false);
            $table->timestamps();

            $table->index('gwdm30_structural_table_id');
        });

        Schema::create('gwdm30_structural_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gwdm30_structural_column_id')
                ->constrained('gwdm30_structural_columns')
                ->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('frequency')->nullable();
            $table->timestamps();

            $table->index('gwdm30_structural_column_id');
        });
    }

    public function down(): void
    {
        // Drop in reverse FK order
        Schema::dropIfExists('gwdm30_structural_values');
        Schema::dropIfExists('gwdm30_structural_columns');
        Schema::dropIfExists('gwdm30_structural_tables');
    }
};
