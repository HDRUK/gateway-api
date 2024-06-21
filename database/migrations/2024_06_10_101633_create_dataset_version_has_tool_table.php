<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dataset_version_has_tool', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tool_id');
            $table->unsignedBigInteger('dataset_version_id');
            $table->timestamps();
            $table->SoftDeletes();
            $table->foreign('tool_id')->references('id')->on('tools')->onDelete('cascade');
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('dataset_version_has_tool');

        Schema::enableForeignKeyConstraints();
    }
};