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
        Schema::create('dataset_has_spatial_coverage', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->bigInteger('dataset_id')->unsigned();
            $table->bigInteger('spatial_coverage_id')->unsigned();
            $table->foreign('dataset_id')->references('id')->on('datasets');
            $table->foreign('spatial_coverage_id')->references('id')->on('spatial_coverage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dataset_has_spatial_coverage');
    }
};
