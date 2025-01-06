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
        Schema::dropIfExists('omop_matcher_cache');
        
        Schema::create('omop_matcher_cache', function (Blueprint $table) {
            $table->id();
            $table->string('search_term', 255);
            $table->string('search_parameters', 255);
            $table->mediumText('result')->nullable();
        
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
        Schema::dropIfExists('omop_matcher_cache');
    }
};

