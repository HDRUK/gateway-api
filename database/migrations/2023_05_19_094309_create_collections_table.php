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
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->char('name', 255)->nullable(false);
            $table->text('description')->nullable(false);
            $table->char('image_link', 255)->nullable(false);
            $table->boolean('enabled')->default(true);
            $table->char('keywords', 255)->nullable(false);
            $table->boolean('public')->default(true);
            $table->bigInteger('counter')->unsigned();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
