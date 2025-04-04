<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('concept_synonym', function (Blueprint $table) {
            $table->integer('concept_id');
            $table->string('concept_synonym_name', 1000)->collation('utf8_bin');
            $table->integer('language_concept_id');

            $table->primary(['concept_id', 'concept_synonym_name', 'language_concept_id']);

            // Foreign keys
            $table->foreign('concept_id')->references('concept_id')->on('concept')->onDelete('cascade');
            $table->foreign('language_concept_id')->references('concept_id')->on('concept')->onDelete('cascade');

            // Indexes
            $table->index('concept_id');
            $table->fullText('concept_synonym_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('concept_synonym');
    }

};
