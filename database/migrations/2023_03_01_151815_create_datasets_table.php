<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('datasets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->char('mongo_id', 255)->nullable();
            $table->boolean('active');
            $table->integer('application_status_author')->nullable();
            $table->integer('application_status_desc')->nullable();
            $table->boolean('commercial_use')->nullable();
            $table->char('dataset_id', 255)->nullable();
            $table->boolean('is_5_safes')->nullable();
            $table->boolean('is_cohort_discovery')->nullable();
            $table->char('license', 255)->nullable();
            $table->char('pid', 255)->nullable();
            $table->json('question_answers')->nullable();
            $table->char('source', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('datasets');
    }
};
