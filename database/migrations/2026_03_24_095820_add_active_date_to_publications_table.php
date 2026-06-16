<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->dateTime('active_date')->nullable()->after('deleted_at')->index();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                UPDATE publications
                SET active_date = now()
                WHERE status = 'ACTIVE'
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $$table->dropIndex(['active_date']);
            $table->dropColumn('active_date');
        });
    }
};
