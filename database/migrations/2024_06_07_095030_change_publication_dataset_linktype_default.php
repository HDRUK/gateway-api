<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the column 'link_type' exists and rename it
        if (Schema::hasColumn('publication_has_dataset', 'link_type')) {
            Schema::table('publication_has_dataset', function (Blueprint $table) {
                $table->renameColumn('link_type', 'link_type_old');
            });

            DB::statement("UPDATE publication_has_dataset SET link_type_old = 'USING' WHERE link_type_old = 'UNKNOWN'");
        }

        // Add new column 'link_type' only if it doesn't exist
        if (!Schema::hasColumn('publication_has_dataset', 'link_type')) {
            Schema::table('publication_has_dataset', function (Blueprint $table) {
                $table->enum('link_type', ['ABOUT', 'USING'])->default('USING');
            });

            DB::statement("UPDATE publication_has_dataset SET link_type = link_type_old");
        }

        // Drop the 'link_type_old' column if it exists
        if (Schema::hasColumn('publication_has_dataset', 'link_type_old')) {
            Schema::table('publication_has_dataset', function (Blueprint $table) {
                $table->dropColumn('link_type_old');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_has_dataset', function (Blueprint $table) {
            if (!Schema::hasColumn('publication_has_dataset', 'link_type')) {
                $table->enum('link_type', ['ABOUT', 'USING', 'UNKNOWN'])->default('UNKNOWN');
            }
        });
    }
};
