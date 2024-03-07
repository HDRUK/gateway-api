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
        Schema::create('dataset_has_tool', function (Blueprint $table) {
            $table->bigInteger('dataset_id')->unsigned();
            $table->bigInteger('tool_id')->unsigned();

            $table->primary(['dataset_id', 'tool_id']);
            $table->foreign('dataset_id')->references('id')->on('dataset')->onDelete('cascade');
            $table->foreign('tool_id')->references('id')->on('tool')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dataset_has_tool');
    }
};
