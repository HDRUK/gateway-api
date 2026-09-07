<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Drops the legacy Mongo->MySQL ETL tracking columns on `datasets`.
     * These were never used as real relationships/joins/foreign keys.
     */
    public function up(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->dropColumn(['mongo_object_id', 'mongo_id', 'mongo_pid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->char('mongo_object_id', 255)->nullable();
            $table->char('mongo_id', 255)->nullable();
            $table->char('mongo_pid', 255)->nullable();
        });
    }
};
