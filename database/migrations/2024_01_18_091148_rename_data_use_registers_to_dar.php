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
        Schema::rename('data_use_registers', 'dur');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('dur', 'data_use_registers');
    }
};
