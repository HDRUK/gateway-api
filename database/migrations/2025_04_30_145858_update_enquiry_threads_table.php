<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->string('enquiry_unique_key', 32);
            $table->bigInteger('team_id');
        });

        DB::statement('UPDATE enquiry_threads SET enquiry_unique_key = unique_key');
        DB::statement('UPDATE enquiry_threads SET team_id = JSON_EXTRACT(team_ids, "$[0]")');

        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->dropColumn(['team_ids']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->json('team_ids');
        });

        DB::statement('UPDATE enquiry_threads SET team_ids = JSON_ARRAY(team_id)');

        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->dropColumn(['team_id', 'enquiry_unique_key']);
        });

    }
};
