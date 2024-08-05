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
        Schema::create('command_config', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('ident', 128);
            $table->json('config')->nullable();
            $table->tinyInteger('enabled')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_config');
    }
};
