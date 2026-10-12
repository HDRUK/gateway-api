<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Supports "max(version) per dataset_id where deleted_at is null" lookups
     * (DatasetVersion::scopeIndexEligible + latest-version resolution) as an
     * index-only seek rather than a per-row dependent subquery.
     */
    public function up(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->index(
                ['dataset_id', 'deleted_at', 'version'],
                'dataset_versions_dataset_deleted_version_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dataset_versions', function (Blueprint $table) {
            $table->dropIndex('dataset_versions_dataset_deleted_version_index');
        });
    }
};
