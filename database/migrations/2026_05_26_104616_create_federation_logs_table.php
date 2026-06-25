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
        Schema::create('federation_job_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('federation_id');
            $table->uuid('pid');
            $table->uuid('job_uuid');
            $table->tinyInteger('status')->nullable()->comment('1 = success, 0 = failed, null = in progress');
            $table->json('details')->nullable();
            $table->unsignedSmallInteger('job_attempts')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('job_uuid');
            $table->index('team_id');
            $table->index('federation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('federation_job_runs');
    }
};
