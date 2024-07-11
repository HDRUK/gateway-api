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
        Schema::table('dataset_version_has_named_entities', function (Blueprint $table) {
            // First, drop the existing foreign keys with cascade
            $table->dropForeign(['dataset_version_id']);
            $table->dropForeign(['named_entities_id']);

            // Add the foreign keys without onDelete('cascade')
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions');
            $table->foreign('named_entities_id')->references('id')->on('named_entities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dataset_version_has_named_entities', function (Blueprint $table) {
            // First, drop the existing foreign keys
            $table->dropForeign(['dataset_version_id']);
            $table->dropForeign(['named_entities_id']);

            // Add the foreign keys with onDelete('cascade')
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions')->onDelete('cascade');
            $table->foreign('named_entities_id')->references('id')->on('named_entities')->onDelete('cascade');
        });
    }
};
