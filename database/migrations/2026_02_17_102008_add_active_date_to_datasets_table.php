<?php

use DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->dateTime('active_date')->nullable()->after('deleted_at');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                UPDATE datasets
                JOIN dataset_versions ON dataset_versions.dataset_id = datasets.id
                AND dataset_versions.version = (
                    SELECT MIN(dv.version)
                    FROM dataset_versions dv
                    WHERE dv.dataset_id = datasets.id
                )
                SET datasets.active_date = dataset_versions.active_date
                WHERE datasets.status = 'ACTIVE'
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->dropColumn('active_date');
        });
    }
};
