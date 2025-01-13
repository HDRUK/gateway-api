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
        Schema::create('dar_application_has_dataset', function (Blueprint $table) {
            $table->bigInteger('dar_application_id')->unsigned();
            $table->bigInteger('dataset_id')->unsigned();

            $table->foreign('dar_application_id')->references('id')->on('dar_applications');
            $table->foreign('dataset_id')->references('id')->on('datasets');

            $table->unique(['dar_application_id', 'dataset_id']);
            $table->index('dar_application_id');
            $table->index('dataset_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dar_application_has_dataset');
    }
};
