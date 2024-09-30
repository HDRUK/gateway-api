<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('concept_relationship', function (Blueprint $table) {
            $table->integer('concept_id_1');
            $table->integer('concept_id_2');
            $table->string('relationship_id', 20);
            $table->date('valid_start_date');
            $table->date('valid_end_date')->nullable();
            $table->char('invalid_reason', 1)->nullable();

            $table->primary(['concept_id_1','concept_id_2', 'relationship_id']);


            // Foreign keys
            $table->foreign('concept_id_1')->references('concept_id')->on('concept')->onDelete('cascade');
            $table->foreign('concept_id_2')->references('concept_id')->on('concept')->onDelete('cascade');

            // Indexes
            $table->index(['concept_id_1', 'valid_end_date']);
            $table->index('concept_id_2');
        });
    }

    public function down()
    {
        Schema::dropIfExists('concept_relationship');
    }
};
