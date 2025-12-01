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
        Schema::create('cancer_type_filters', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('filter_id')->unique()->comment('Unique identifier like 0_0, 0_0_0, etc.');
            $table->string('label');
            $table->string('category')->nullable();
            $table->string('primary_group')->nullable();
            $table->string('count')->default('0');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->default(0)->comment('Depth level in the hierarchy');
            $table->integer('sort_order')->default(0);
            
            $table->foreign('parent_id')->references('id')->on('cancer_type_filters')->onDelete('cascade');
            $table->index('filter_id');
            $table->index('parent_id');
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancer_type_filters');
    }
};
