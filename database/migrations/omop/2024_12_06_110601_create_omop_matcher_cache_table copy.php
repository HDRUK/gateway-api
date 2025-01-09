<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('saved_mvcm_results', function (Blueprint $table) {
            $table->id();
            $table->string('search_term', 255); // Reduced to fit within the key limit
            $table->string('search_parameters', 255)->nullable();
            $table->mediumText('result');
        
            $table->unique([
                'search_term',
                'search_parameters'
            ], 'omop_matcher_cache_unique');
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('saved_mvcm_results');
    }
};

