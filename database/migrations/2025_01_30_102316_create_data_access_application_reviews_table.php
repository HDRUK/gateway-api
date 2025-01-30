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
        Schema::create('dar_application_reviews', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->bigInteger('application_id')->unsigned();
            $table->foreign('application_id')->references('id')->on('dar_applications');
            $table->bigInteger('question_id')->unsigned()->nullable();
            $table->foreign('question_id')->references('id')->on('question_bank_questions');
            $table->text('review_comment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dar_application_reviews');
    }
};
