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
        Schema::create('dur_outputs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('dur_id')->unsigned();
            $table->string('type')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->nullable();
            $table->text('detail')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('dur_id')->references('id')->on('dur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dur_outputs');
    }
};
