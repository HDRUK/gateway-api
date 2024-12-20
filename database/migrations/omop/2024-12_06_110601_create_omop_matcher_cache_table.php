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
        Schema::create('omop_matcher_cache', function (Blueprint $table) {
            $table->id();
            $table->string('search_term', 255);
            $table->string('vocabulary_id', 255)->nullable();
            $table->enum('concept_ancestor', ['y', 'n']);
            $table->enum('concept_relationship', ['y', 'n']);
            $table->enum('concept_synonym', ['y', 'n']);
            $table->string('concept_synonym_types', 255)->nullable();
            $table->float('search_threshold');
            $table->integer('max_separation_descendant');
            $table->integer('max_separation_ancestor');
            $table->mediumText('result');
            
        // First part of the unique index
        $table->unique([
            'search_term',
            'vocabulary_id',
            'search_threshold',
        ], 'term_cache');

        // Second part of the unique index
        $table->unique([
            'concept_synonym',
            'concept_synonym_types',
            'concept_ancestor',
            'concept_relationship'
            'max_separation_descendant',
            'max_separation_ancestor'
        ], 'param_cache');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('omop_matcher_cache');
    }
};
