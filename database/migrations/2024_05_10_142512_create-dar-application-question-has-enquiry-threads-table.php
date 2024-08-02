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
        Schema::create('dar_app_q_has_enq_threads', function (Blueprint $table) {
            $table->bigInteger('equiry_thread_id');
            $table->bigInteger('dar_application_q_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dar_app_q_has_enq_threads');
    }
};
