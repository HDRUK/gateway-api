<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('federations', function (Blueprint $table) {
            $table->timestamp('enabled_at')->nullable()->after('enabled');
        });

        // Best guess for pre-existing rows: a currently enabled federation
        // was first enabled no later than its creation.
        DB::table('federations')
            ->where('enabled', 1)
            ->update(['enabled_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('federations', function (Blueprint $table) {
            $table->dropColumn('enabled_at');
        });
    }
};
