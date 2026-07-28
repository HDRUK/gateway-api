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
        Schema::table('federation_job_runs', function (Blueprint $table) {
            $table->index(['federation_id', 'job_uuid', 'pid'], 'federation_job_runs_federation_job_pid_index');
            $table->index(['federation_id', 'job_uuid', 'created_at'], 'federation_job_runs_federation_job_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('federation_job_runs', function (Blueprint $table) {
            $table->dropIndex('federation_job_runs_federation_job_pid_index');
            $table->dropIndex('federation_job_runs_federation_job_created_index');
        });
    }
};
