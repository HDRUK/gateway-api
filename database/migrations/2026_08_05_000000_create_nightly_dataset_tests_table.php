<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('nightly_dataset_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dataset_id')->unique();
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nightly_dataset_tests');
    }
};
