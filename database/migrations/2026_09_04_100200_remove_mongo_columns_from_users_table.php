<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Drops the legacy Mongo->MySQL ETL tracking columns on `users`.
     * These were never used as real relationships/joins/foreign keys.
     * The `mongo_id` index (added in 2024_07_12_122247_add-indexes-to-all-tables)
     * must be dropped before the column itself.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['mongo_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mongo_object_id', 'mongo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mongo_object_id')->nullable();
            $table->bigInteger('mongo_id')->default(0);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('mongo_id');
        });
    }
};
