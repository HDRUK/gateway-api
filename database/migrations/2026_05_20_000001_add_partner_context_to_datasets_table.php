<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->string('partner_context')->default('HDRUK')->after('is_cohort_discovery');
        });

        DB::table('datasets')->update(['partner_context' => 'HDRUK']);
    }

    public function down(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->dropColumn('partner_context');
        });
    }
};
