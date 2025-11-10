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
        Schema::dropIfExists('datasets');

        Schema::create('datasets', function (Blueprint $table) {
            $table->id();

            $table->char('mongo_object_id', 255)->nullable();
            $table->char('mongo_id', 255)->nullable();
            $table->char('mongo_pid', 255)->nullable();
            $table->char('datasetid', 255)->nullable();
            $table->json('datasetv2')->nullable();
            $table->char('source', 255)->nullable();
            $table->integer('discourse_topic_id')->default(0);
            $table->boolean('is_cohort_discovery')->default(0);
            $table->boolean('commercial_use')->default(0);

            $table->integer('state_id')->default(0);
            $table->integer('uploader_id')->default(0);
            $table->integer('metadataquality_id')->default(0);

            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('team_id')->unsigned();

            $table->char('label', 255)->nullable(false);
            $table->char('short_description', 255)->nullable(false);
            $table->json('dataset')->nullable();

            $table->integer('views_count')->default(0);
            $table->integer('views_prev_count')->default(0);

            $table->boolean('has_technical_details')->default(0);

            $table->timestamp('created')->nullable();
            $table->timestamp('updated')->nullable();
            $table->timestamp('submitted')->nullable();
            $table->timestamp('published')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('team_id')->references('id')->on('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('datasets');
        Schema::enableForeignKeyConstraints();

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
};
