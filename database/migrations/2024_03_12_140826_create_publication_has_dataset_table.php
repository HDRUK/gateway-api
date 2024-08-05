<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('publication_has_dataset', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('publication_id')->unsigned();
            $table->bigInteger('dataset_id')->unsigned();
            $table->foreign('publication_id')->references('id')->on('publications');
            $table->foreign('dataset_id')->references('id')->on('datasets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publication_has_dataset');
    }
};
