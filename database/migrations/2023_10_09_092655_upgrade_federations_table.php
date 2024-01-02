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
        Schema::table('federations', function (Blueprint $table) {
            $table->string('federation_type', 255)->nullable(false)->after('id');
            $table->string('run_time_minute', 2)->default('00')->after('run_time_hour');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('federations', function (Blueprint $table) {
            $table->dropColumn([
                'federation_type',
                'run_time_minute',
            ]);
        });
    }
};
