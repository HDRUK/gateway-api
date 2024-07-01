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
        // Drop the foreign key constraints
        Schema::table('collection_has_datasets', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropForeign(['dataset_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['application_id']);
        });

        // Rename the table
        Schema::rename('collection_has_datasets', 'collection_has_dataset_version');

        // Rename the column
        Schema::table('collection_has_dataset_version', function (Blueprint $table) {
            $table->renameColumn('dataset_id', 'dataset_version_id');
        });

        // Add the foreign key constraints back
        Schema::table('collection_has_dataset_version', function (Blueprint $table) {
            $table->foreign('collection_id')->references('id')->on('collections')->onDelete('cascade');
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraints
        Schema::table('collection_has_dataset_version', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropForeign(['dataset_version_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['application_id']);
        });

        // Rename the column back
        Schema::table('collection_has_dataset_version', function (Blueprint $table) {
            $table->renameColumn('dataset_version_id', 'dataset_id');
        });

        // Rename the table back
        Schema::rename('collection_has_dataset_version', 'collection_has_datasets');

        // Add the foreign key constraints back
        Schema::table('collection_has_datasets', function (Blueprint $table) {
            $table->foreign('collection_id')->references('id')->on('collections')->onDelete('cascade');
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('set null');
        });
    }
};
