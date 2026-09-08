<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Drops the legacy Mongo->MySQL ETL tracking columns on `tools`.
     * These were never used as real relationships/joins/foreign keys.
     */
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->dropColumn(['mongo_object_id', 'mongo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->char('mongo_object_id', 24)->nullable();
            $table->char('mongo_id', 255)->nullable()->after('mongo_object_id');
        });
    }
};
