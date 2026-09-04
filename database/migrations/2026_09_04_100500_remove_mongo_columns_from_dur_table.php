<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Drops the legacy Mongo->MySQL ETL tracking columns on `dur`.
     * These were never used as real relationships/joins/foreign keys.
     * The `mongo_object_id` index (added in 2024_07_12_122247_add-indexes-to-all-tables)
     * must be dropped before the column itself.
     */
    public function up(): void
    {
        Schema::table('dur', function (Blueprint $table) {
            $table->dropIndex(['mongo_object_id']);
        });

        Schema::table('dur', function (Blueprint $table) {
            $table->dropColumn(['mongo_object_id', 'mongo_id', 'mongo_object_dar_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dur', function (Blueprint $table) {
            $table->char('mongo_object_dar_id', 24)->nullable(); // projectId which is data_requests._id (mongo)
            $table->char('mongo_object_id', 24)->nullable(); // _id (mongo)
            $table->char('mongo_id', 255)->nullable(); // id
        });

        Schema::table('dur', function (Blueprint $table) {
            $table->index('mongo_object_id');
        });
    }
};
