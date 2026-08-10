<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('dataset_link_check_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dataset_id');
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');
            $table->string('url', 2048);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamps();

            $table->index('dataset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dataset_link_check_results');
    }
};
