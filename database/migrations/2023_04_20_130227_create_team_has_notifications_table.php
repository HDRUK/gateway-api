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
        Schema::create('team_has_notifications', function (Blueprint $table) {
            $table->bigInteger('team_id')->unsigned();
            $table->bigInteger('notification_id')->unsigned();
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('notification_id')->references('id')->on('notifications');
            $table->unique(['team_id', 'notification_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_has_notifications');
    }
};
