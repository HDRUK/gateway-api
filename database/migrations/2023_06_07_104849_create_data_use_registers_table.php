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
        Schema::create('data_use_registers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('counter');
            $table->json('keywords')->nullable();
            $table->json('dataset_ids');
            $table->json('gateway_dataset_ids');
            $table->json('non_gateway_dataset_ids')->nullable();
            $table->json('gateway_applicants')->nullable();
            $table->json('non_gateway_applicants')->nullable();
            $table->json('funders_and_sponsors')->nullable();
            $table->json('other_approval_committees')->nullable();
            $table->json('gateway_output_tools')->nullable();
            $table->json('gateway_output_papers')->nullable();
            $table->json('non_gateway_outputs')->nullable();
            $table->char('project_title', 255);
            $table->char('project_id_text', 64);
            $table->char('organisation_name', 128);
            $table->char('organisation_sector', 128);
            $table->char('lay_summary', 128)->nullable();
            // $table->dateTime('latest_approval_date')->nullable();
            // $table->tinyInteger('enabled')->nullable();
            $table->bigInteger('team_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            // $table->dateTime('last_activity')->nullable();
            // $table->tinyInteger('manual_upload')->nullable();
            $table->char('rejection_reason', 255)->nullable();
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_use_registers');
    }
};
