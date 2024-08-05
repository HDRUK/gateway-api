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
        Schema::create('tool_has_programming_language', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('tool_id')->unsigned();
            $table->bigInteger('programming_language_id')->unsigned();

            $table->foreign('tool_id')->references('id')->on('tools');
            $table->foreign('programming_language_id')->references('id')->on('programming_languages');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_has_programming_language');
    }
};
