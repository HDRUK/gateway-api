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
        Schema::create('data_use_register_has_keywords', function (Blueprint $table) {
            $table->bigInteger('data_use_register_id')->unsigned();
            $table->bigInteger('keyword_id')->unsigned();

            $table->foreign('data_use_register_id')->references('id')->on('data_use_registers');
            $table->foreign('keyword_id')->references('id')->on('keywords');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_use_register_has_keywords');
    }
};
