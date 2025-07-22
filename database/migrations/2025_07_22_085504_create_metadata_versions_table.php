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
        Schema::create('metadata_versions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->bigInteger('dataset_id')->unsigned();
            $table->integer('version')->unsigned();
            $table->json('patch'); // RFC6902 Patch Document
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metadata_versions');
    }
};
