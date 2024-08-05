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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('event_type', 255);
            $table->integer('user_type_id');
            $table->integer('log_type_id');
            $table->integer('user_id');
            $table->string('version')->nullable();
            $table->string('html', 255)->default('')->nullable();
            $table->string('plain_text', 255)->default('')->nullable();
            $table->string('user_id_mongo', 32)->nullable();
            $table->string('version_id_mongo', 32)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
