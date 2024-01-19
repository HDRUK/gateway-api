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
        Schema::table('data_use_registers', function (Blueprint $table) {
            $table->dropColumn([
                'dataset_id',
                'enabled',
                'user_id',
                'ro_crate',
                'organization_name',
                'project_title',
                'lay_summary',
                'public_benefit_statement',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_use_registers', function (Blueprint $table) {
            $table->bigInteger('dataset_id')->unsigned();
            $table->boolean('enabled')->default(true);
            $table->bigInteger('user_id')->unsigned();
            $table->mediumText('ro_crate')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('project_title')->nullable();
            $table->string('lay_summary')->nullable();
            $table->string('public_benefit_statement')->nullable();
        });
    }
};
