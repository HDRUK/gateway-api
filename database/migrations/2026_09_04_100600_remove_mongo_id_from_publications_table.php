<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Drops the legacy Mongo->MySQL ETL tracking column on `publications`.
     * This was never used as a real relationship/join/foreign key.
     */
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->dropColumn('mongo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->char('mongo_id', 255)->nullable();
        });
    }
};
