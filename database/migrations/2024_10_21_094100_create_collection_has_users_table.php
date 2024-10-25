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
        Schema::create('collection_has_users', function (Blueprint $table) {
            $table->bigInteger('collection_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->string('role')->nullable();

            $table->foreign('collection_id')->references('id')->on('collections');
            $table->foreign('user_id')->references('id')->on('users');

            $table->unique(['collection_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_has_users');
    }
};
