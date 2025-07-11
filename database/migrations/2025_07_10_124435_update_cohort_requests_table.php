<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('cohort_requests', function (Blueprint $table) {
            $table->string('access_to_env', 15)->default('NONE');
        });
    }

    public function down(): void
    {
        Schema::table('cohort_requests', function (Blueprint $table) {
            $table->dropColumn('access_to_env');
        });
    }
};
