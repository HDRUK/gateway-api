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
            $table->string('search_term', 191); // Reduced to fit within the key limit
            $table->string('vocabulary_id', 191)->nullable();
            $table->enum('concept_ancestor', ['y', 'n']);
            $table->enum('concept_relationship', ['y', 'n']);
            $table->enum('concept_synonym', ['y', 'n']);
            $table->string('concept_relationship_types', 191)->nullable(); 
            $table->float('search_threshold');
            $table->integer('max_separation_descendant');
            $table->integer('max_separation_ancestor');
            $table->mediumText('result');
        
            $table->unique([
                'search_term',
                'vocabulary_id',
                'concept_ancestor',
                'concept_relationship',
                'concept_relationship_types',
                'concept_synonym',
                'search_threshold',
                'max_separation_descendant',
                'max_separation_ancestor'
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

