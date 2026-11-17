<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each federation run now records a row of its own, with no pid, alongside
 * the per-dataset rows that share its job_uuid.
 */
return new class () extends Migration {
    private const RUN_ROW_INDEX = 'federation_job_runs_one_run_row_per_job';

    public function up(): void
    {
        Schema::table('federation_job_runs', function (Blueprint $table) {
            $table->uuid('pid')->nullable()->change();
        });

        DB::statement('CREATE UNIQUE INDEX ' . self::RUN_ROW_INDEX . ' ON federation_job_runs ((CASE WHEN pid IS NULL THEN job_uuid END))');
    }

    public function down(): void
    {
        Schema::table('federation_job_runs', function (Blueprint $table) {
            $table->dropIndex(self::RUN_ROW_INDEX);
        });

        DB::table('federation_job_runs')->whereNull('pid')->delete();

        Schema::table('federation_job_runs', function (Blueprint $table) {
            $table->uuid('pid')->nullable(false)->change();
        });
    }
};
