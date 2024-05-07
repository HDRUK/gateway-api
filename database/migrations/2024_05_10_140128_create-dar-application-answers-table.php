<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dar_application_answers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->bigInteger('question_id');
            $table->bigInteger('application_id');
            $table->json('answer');
            $table->bigInteger('contributor_id'); // user_id - person who answered question
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dar_application_answers');
    }
};
