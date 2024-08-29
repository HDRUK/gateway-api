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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('tool_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->tinyInteger('rating')->default('1');
            $table->text('review_text');
            $table->char('review_state', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tool_id')->references('id')->on('tools');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
