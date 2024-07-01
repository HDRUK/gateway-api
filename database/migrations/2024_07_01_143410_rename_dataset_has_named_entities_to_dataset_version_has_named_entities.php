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
        // Drop the foreign key constraint
        Schema::table('dataset_has_named_entities', function (Blueprint $table) {
            $table->dropForeign(['dataset_id']);
        });

        // Rename the table
        Schema::rename('dataset_has_named_entities', 'dataset_version_has_named_entities');

        // Rename the column
        Schema::table('dataset_version_has_named_entities', function (Blueprint $table) {
            $table->renameColumn('dataset_id', 'dataset_version_id');
        });

        // Add the foreign key constraint back
        Schema::table('dataset_version_has_named_entities', function (Blueprint $table) {
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraint
        Schema::table('dataset_version_has_named_entities', function (Blueprint $table) {
            $table->dropForeign(['dataset_version_id']);
        });

        // Rename the column back
        Schema::table('dataset_version_has_named_entities', function (Blueprint $table) {
            $table->renameColumn('dataset_version_id', 'dataset_id');
        });

        // Rename the table back
        Schema::rename('dataset_version_has_named_entities', 'dataset_has_named_entities');

        // Add the foreign key constraint back
        Schema::table('dataset_has_named_entities', function (Blueprint $table) {
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');
        });
    }
};
