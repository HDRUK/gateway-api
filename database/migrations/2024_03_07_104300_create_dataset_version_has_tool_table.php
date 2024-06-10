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
        Schema::create('dataset_version_has_tools', function (Blueprint $table) {
            $table->bigInteger('dataset_version_id')->unsigned();
            $table->bigInteger('tool_id')->unsigned();

            $table->primary(['dataset_version_id', 'tool_id']);
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions')->onDelete('cascade');
            $table->foreign('tool_id')->references('id')->on('tools')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dataset_version_has_tools');
    }
};
