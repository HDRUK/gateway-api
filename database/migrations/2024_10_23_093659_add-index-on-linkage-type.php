<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dataset_version_has_dataset_version', function (Blueprint $table) {
            $table->index('linkage_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dataset_version_has_dataset_version', function (Blueprint $table) {
            $table->dropIndex('dataset_version_has_dataset_version_linkage_type_index');
        });
    }
};
