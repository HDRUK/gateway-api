<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            Schema::table('publications', function (Blueprint $table) {
                $table->enum('status', ['ACTIVE', 'DRAFT', 'ARCHIVED'])->default('DRAFT');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('publications')) {
            Schema::table('publications', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
