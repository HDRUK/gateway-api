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
        Schema::create('tool_has_tags', function (Blueprint $table) {
            $table->bigInteger('tool_id')->unsigned();
            $table->bigInteger('tag_id')->unsigned();
            $table->foreign('tool_id')->references('id')->on('tools');
            $table->foreign('tag_id')->references('id')->on('tags');
            $table->unique(['tool_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_has_tags');
    }
};
