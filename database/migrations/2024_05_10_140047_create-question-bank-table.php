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
        Schema::create('question_bank_questions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->bigInteger('section_id');
            $table->bigInteger('user_id'); //needed?
            $table->bigInteger('team_id')->nullable(); //null means not associated to any team
            $table->tinyInteger('locked');
            //$table->bigInteger('latest_version'); // question_bank_versions
            $table->tinyInteger('archived');
            $table->dateTime('archived_date')->nullable();
            //may also need archived_comments
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_bank_questions');
    }
};
