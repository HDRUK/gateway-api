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
        Schema::table('publication_has_dataset_version', function (Blueprint $table) {
            // Drop the 'deleted_at' column to remove soft deletes
            if (Schema::hasColumn('publication_has_dataset_version', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_has_dataset_version', function (Blueprint $table) {
            // Re-add the 'deleted_at' column to re-enable soft deletes
            $table->softDeletes();
        });
    }
};
