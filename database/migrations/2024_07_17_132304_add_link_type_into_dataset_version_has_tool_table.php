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
        Schema::table('dataset_version_has_tool', function (Blueprint $table) {
            $table->string('link_type', 255)->default('Used on')->after('dataset_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('dataset_version_has_tool', 'link_type')) {
            Schema::table('dataset_version_has_tool', function (Blueprint $table) {
                $table->dropColumn([
                    'link_type',
                ]);
            });
        }
    }
};
