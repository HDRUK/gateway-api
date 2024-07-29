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
        if (Schema::hasTable('collections')) {
            Schema::table('collections', function (Blueprint $table) {
                $table->enum('status', ['ACTIVE', 'DRAFT', 'ARCHIVED'])->default('DRAFT');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('collections')) {
            Schema::table('collections', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};