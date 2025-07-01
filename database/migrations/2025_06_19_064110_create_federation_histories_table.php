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
        Schema::create('federation_run_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('federations_id');
            $table->dateTime('run_at');
            $table->enum('result', ['success', 'error']);
            $table->text('output')->nullable();
            $table->text('errors')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('federation_run_histories');
    }
};
