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
        Schema::table('collections', function (Blueprint $table) {
            $table->bigInteger('team_id')->nullable()->default(null)->unsigned();

            $table->foreign('team_id')->references('id')->on('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::statement('ALTER TABLE `collections` DROP FOREIGN KEY `collections_team_id_foreign`');

        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('team_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
