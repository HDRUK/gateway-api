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
        Schema::create('publication_has_keywords', function (Blueprint $table) {
            $table->bigInteger('publication_id')->unsigned();
            $table->bigInteger('keyword_id')->unsigned();

            $table->foreign('publication_id')->references('id')->on('collections');
            $table->foreign('keyword_id')->references('id')->on('keywords');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publication_has_keywords');
    }
};
