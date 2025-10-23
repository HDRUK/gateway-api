<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dur', function (Blueprint $table) {
            $table->bigInteger('sector_id')->nullable()->default(null)->unsigned();

            $table->foreign('sector_id')->references('id')->on('sectors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('dur', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sector_id');
        });
        Schema::enableForeignKeyConstraints();
    }
};
