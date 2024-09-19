<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('standard_concepts', function (Blueprint $table) {
            $table->integer('concept_id')->primary();
            $table->string('concept_name', 500);
            $table->string('domain_id', 20);
            $table->string('vocabulary_id', 20);
            $table->string('concept_class_id', 20);
            $table->char('standard_concept', 1)->nullable();
            $table->string('concept_code', 50);
            $table->date('valid_start_date');
            $table->date('valid_end_date')->nullable();
            $table->char('invalid_reason', 1)->nullable();

            // Indexes
            $table->index('concept_id');
            $table->index(['standard_concept', 'vocabulary_id', 'concept_id'], 'sc_voc_id_concept_index');

            // Full-text index
            //DB::statement('CREATE FULLTEXT INDEX idx_sc_concept_name ON standard_concepts (concept_name)');
        });
    }

    public function down()
    {
        Schema::dropIfExists('standard_concepts');
    }

};
