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
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->char('search_endpoint', 24)->nullable();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['search_endpoint', 'user_id']);
        });
    }
};
