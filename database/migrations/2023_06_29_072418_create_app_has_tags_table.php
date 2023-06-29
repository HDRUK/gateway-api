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
        Schema::create('app_has_tags', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('app_id')->unsigned();
            $table->bigInteger('tag_id')->unsigned();

            $table->foreign('app_id')->references('id')->on('app_registrations');
            $table->foreign('tag_id')->references('id')->on('tags');

            $table->unique(['app_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_has_tags');
    }
};
