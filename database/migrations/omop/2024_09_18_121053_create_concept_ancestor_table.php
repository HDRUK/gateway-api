<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('concept_ancestor', function (Blueprint $table) {
            $table->integer('ancestor_concept_id');
            $table->integer('descendant_concept_id');
            $table->integer('min_levels_of_separation');
            $table->integer('max_levels_of_separation');

            $table->primary(['ancestor_concept_id', 'descendant_concept_id']);

            // Foreign keys
            $table->foreign('ancestor_concept_id')->references('concept_id')->on('concept')->onDelete('cascade');
            $table->foreign('descendant_concept_id')->references('concept_id')->on('concept')->onDelete('cascade');

            // Indexes
            $table->index('descendant_concept_id');
            $table->index('ancestor_concept_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('concept_ancestor');
    }
};
