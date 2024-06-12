<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dataset_version_has_dataset_version', function (Blueprint $table) {
            $table->bigIncrements('id'); // Optional if you want a primary key

            $table->unsignedBigInteger('dataset_version_source_id');
            $table->unsignedBigInteger('dataset_version_target_id');
            $table->string('linkage_type');
            $table->boolean('direct_linkage');
            $table->text('description')->nullable();

            // Unique key for combination of dataset_version_source_id, dataset_version_target_id, and linkage_type
            $table->unique(['dataset_version_source_id', 'dataset_version_target_id', 'linkage_type'], 'linkage_unique');

            // Indexes for foreign keys
            $table->index('dataset_version_source_id', 'dataset_version_1_fk_idx');
            $table->index('dataset_version_target_id', 'dataset_version_2_fk_idx');

            // Foreign key constraints
            $table->foreign('dataset_version_source_id', 'ld_dataset_version_source_id_fk')
                  ->references('id')->on('dataset_versions')
                  ->onDelete('cascade');

            $table->foreign('dataset_version_target_id', 'ld_dataset_version_target_id_fk')
                  ->references('id')->on('dataset_versions')
                  ->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dataset_version_has_dataset_version');
    }
};
