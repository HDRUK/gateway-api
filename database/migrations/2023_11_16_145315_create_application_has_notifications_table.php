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
        Schema::create('application_has_notifications', function (Blueprint $table) {
            $table->bigInteger('application_id')->unsigned();
            $table->bigInteger('notification_id')->unsigned();

            $table->foreign('application_id')->references('id')->on('applications');
            $table->foreign('notification_id')->references('id')->on('notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_has_notifications');
    }
};
