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
        Schema::create('cohort_request_has_permissions', function (Blueprint $table) {
            $table->bigInteger('cohort_request_id')->unsigned();
            $table->bigInteger('permission_id')->unsigned();

            $table->foreign('cohort_request_id')->references('id')->on('cohort_requests');
            $table->foreign('permission_id')->references('id')->on('permissions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cohort_request_has_permissions');
    }
};
