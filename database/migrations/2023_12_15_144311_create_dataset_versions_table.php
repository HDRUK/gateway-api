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
        Schema::create('dataset_versions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->bigInteger('dataset_id')->unsigned();
            $table->text('metadata_json');
            $table->string('version');

            $table->foreign('dataset_id')->references('id')->on('datasets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dataset_versions');
    }
};
