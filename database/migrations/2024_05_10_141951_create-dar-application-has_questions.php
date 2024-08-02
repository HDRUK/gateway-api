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
        Schema::create('dar_application_has_questions', function (Blueprint $table) {
            $table->bigInteger('application_id');
            $table->bigInteger('question_id');
            $table->text('guidance');
            $table->tinyInteger('required');
            $table->integer('order');
            $table->string('teams', 255)->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dar_application_has_questions');
    }
};
