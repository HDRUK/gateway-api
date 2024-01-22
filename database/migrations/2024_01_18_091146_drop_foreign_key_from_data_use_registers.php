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
        Schema::table('data_use_registers', function (Blueprint $table) {
            $table->dropForeign('data_use_registers_user_id_foreign');
            $table->dropForeign('data_use_registers_dataset_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_use_registers', function (Blueprint $table) {
            $table->foreign('dataset_id')->references('id')->on('datasets');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
