<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Drops the legacy Mongo->MySQL ETL tracking column on `teams`.
     * This was never used as a real relationship/join/foreign key.
     * The `mongo_object_id` index (added in 2024_07_12_122247_add-indexes-to-all-tables)
     * must be dropped before the column itself.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['mongo_object_id']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('mongo_object_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('mongo_object_id', 64)->nullable()->default(null);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->index('mongo_object_id');
        });
    }
};
