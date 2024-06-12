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
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('data_use_registers');

        Schema::enableForeignKeyConstraints();
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('data_use_registers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->boolean('enabled')->default(true);
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('dataset_id')->unsigned(); 
            $table->mediumText('ro_crate')->nullable();
            $table->foreign('dataset_id')->references('id')->on('datasets');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('organization_name')->nullable();
            $table->string('project_title')->nullable();
            $table->string('lay_summary')->nullable();
            $table->string('public_benefit_statement')->nullable();
        });
    }
};
