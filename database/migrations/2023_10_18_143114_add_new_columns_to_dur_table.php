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
        if (Schema::hasTable('data_use_registers')) {
            Schema::table('data_use_registers', function (Blueprint $table) {
                $table->string('organization_name')->nullable();
                $table->string('project_title')->nullable();
                $table->string('lay_summary')->nullable();
                $table->string('public_benefit_statement')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('data_use_registers')) {
            Schema::table('data_use_registers', function (Blueprint $table) {
                $table->dropColumn('organization_name');
                $table->dropColumn('project_title');
                $table->dropColumn('lay_summary');
                $table->dropColumn('public_benefit_statement');
            });
        }
    }
};
