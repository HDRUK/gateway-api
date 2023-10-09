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
            $table->tinyInteger('run_time_minute')->between(0, 59)->after('run_time_hour');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('federations', function (Blueprint $table) {
            //
        });
    }
};
