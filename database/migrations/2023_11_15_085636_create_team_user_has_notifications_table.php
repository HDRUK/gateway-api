<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_user_has_notifications', function (Blueprint $table) {
            $table->bigInteger('team_has_user_id')->unsigned();
            $table->bigInteger('notification_id')->unsigned();

            $table->foreign('team_has_user_id')->references('id')->on('team_has_users');
            $table->foreign('notification_id')->references('id')->on('notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_user_has_notifications');
    }
};
