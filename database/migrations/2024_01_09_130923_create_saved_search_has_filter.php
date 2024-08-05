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
        Schema::create('saved_search_has_filters', function (Blueprint $table) {
            $table->bigInteger('saved_search_id')->unsigned();
            $table->bigInteger('filter_id')->unsigned();
            $table->foreign('saved_search_id')->references('id')->on('saved_searches');
            $table->foreign('filter_id')->references('id')->on('filters');
            $table->unique(['saved_search_id', 'filter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_search_has_filters');
    }
};
