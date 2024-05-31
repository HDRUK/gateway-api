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
        Schema::create('dur_has_tools', function (Blueprint $table) {
            $table->bigInteger('dur_id')->unsigned();
            $table->bigInteger('tool_id')->unsigned();

            $table->foreign('dur_id')->references('id')->on('dur');
            $table->foreign('tool_id')->references('id')->on('tools');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dur_has_tools');
    }
};
