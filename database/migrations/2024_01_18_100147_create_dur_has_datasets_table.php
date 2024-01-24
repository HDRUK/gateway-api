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
        Schema::create('dur_has_datasets', function (Blueprint $table) {
            $table->bigInteger('dur_id')->unsigned();
            $table->bigInteger('dataset_id')->unsigned();
            $table->bigInteger('user_id')->nullable()->default(null)->unsigned();
            $table->bigInteger('application_id')->nullable()->default(null)->unsigned();

            $table->foreign('dur_id')->references('id')->on('dur');
            $table->foreign('dataset_id')->references('id')->on('datasets');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('application_id')->references('id')->on('applications');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_use_register_has_datasets');
    }
};
